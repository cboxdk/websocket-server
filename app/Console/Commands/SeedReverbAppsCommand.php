<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReverbApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Throwable;

/**
 * Seed reverb_applications rows from the REVERB_SEED_APPS env var.
 *
 * The env carries a JSON array of app definitions. Run as a oneshot at
 * pod boot (after migrate, before php-fpm/reverb) so the instance comes
 * up with its apps already provisioned — no race with the admin API.
 *
 * Idempotent: existing rows are upserted by id, so secret rotation or
 * allowed-origin changes just need a redeploy with new env. Rows that
 * exist in the DB but are absent from the env are left alone — the
 * admin API is the authority for ad-hoc app creation, the env is only
 * for the bootstrap set.
 *
 * Exit codes:
 *   0 — success (including no env set / empty array)
 *   1 — invalid JSON or schema, or write failure
 *
 * Schema per entry:
 *   id                       string,  required (primary key)
 *   key                      string,  required (must be unique across apps)
 *   secret                   string,  required
 *   name                     string,  required
 *   allowed_origins          array of string, optional, default ["*"]
 *   enable_client_messages   bool,    optional, default false
 *   max_connections          int|null, optional
 *   max_message_size         int,     optional, default 10000
 *   options                  object,  optional
 */
class SeedReverbAppsCommand extends Command
{
    protected $signature = 'reverb:seed-from-env';

    protected $description = 'Seed reverb_applications from the REVERB_SEED_APPS env var (JSON array).';

    public function handle(): int
    {
        $raw = (string) env('REVERB_SEED_APPS', '');
        if (trim($raw) === '') {
            $this->info('REVERB_SEED_APPS empty — nothing to seed.');

            return self::SUCCESS;
        }

        try {
            $decoded = json_decode($raw, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('REVERB_SEED_APPS is not valid JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! is_array($decoded)) {
            $this->error('REVERB_SEED_APPS must decode to a JSON array.');

            return self::FAILURE;
        }

        $validator = Validator::make(['apps' => $decoded], [
            'apps' => ['array'],
            'apps.*.id' => ['required', 'string'],
            'apps.*.key' => ['required', 'string'],
            'apps.*.secret' => ['required', 'string'],
            'apps.*.name' => ['required', 'string'],
            'apps.*.allowed_origins' => ['sometimes', 'array'],
            'apps.*.allowed_origins.*' => ['string'],
            'apps.*.enable_client_messages' => ['sometimes', 'boolean'],
            'apps.*.max_connections' => ['sometimes', 'nullable', 'integer'],
            'apps.*.max_message_size' => ['sometimes', 'integer'],
            'apps.*.options' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            $this->error('REVERB_SEED_APPS schema invalid:');
            foreach ($validator->errors()->all() as $err) {
                $this->line('  - '.$err);
            }

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        try {
            foreach ($decoded as $entry) {
                $attrs = [
                    'key' => $entry['key'],
                    'secret' => $entry['secret'],
                    'name' => $entry['name'],
                    'allowed_origins' => $entry['allowed_origins'] ?? ['*'],
                    'enable_client_messages' => $entry['enable_client_messages'] ?? false,
                    'max_connections' => $entry['max_connections'] ?? null,
                    'max_message_size' => $entry['max_message_size'] ?? 10000,
                    'options' => $entry['options'] ?? null,
                ];

                $existing = ReverbApplication::query()->find($entry['id']);

                if ($existing === null) {
                    ReverbApplication::query()->create(['id' => $entry['id'], ...$attrs]);
                    $created++;
                    $this->info("Created app [{$entry['id']}]");
                } else {
                    $existing->fill($attrs);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $updated++;
                        $this->info("Updated app [{$entry['id']}]");
                    } else {
                        $this->line("App [{$entry['id']}] already up-to-date");
                    }
                }
            }
        } catch (Throwable $e) {
            $this->error('Failed to upsert app: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Seed complete — %d created, %d updated, %d total in env.',
            $created,
            $updated,
            count($decoded),
        ));

        return self::SUCCESS;
    }
}
