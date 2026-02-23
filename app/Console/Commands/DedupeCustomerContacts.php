<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Console\Command;

class DedupeCustomerContacts extends Command
{
    protected $signature = 'app:dedupe-customer-contacts
        {--dry-run : Show changes without deleting anything}
        {--by=default : Deduplication key (default|name)}
        {--customer-id= : Only process a single customer id}
        {--customer-code= : Only process a single customer code}';

    protected $description = 'Remove duplicated customer contacts (by email/phone/name+position) and keep a single merged record.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $by = (string) ($this->option('by') ?? 'default');
        if (!in_array($by, ['default', 'name'], true)) {
            $this->error("Invalid --by value. Allowed: default, name");
            return self::FAILURE;
        }

        $query = Customer::query()->select(['id', 'code']);

        $customerId = $this->option('customer-id');
        if (is_string($customerId) && $customerId !== '') {
            $query->whereKey((int) $customerId);
        }

        $customerCode = $this->option('customer-code');
        if (is_string($customerCode) && $customerCode !== '') {
            $query->where('code', $customerCode);
        }

        $deleted = 0;
        $updated = 0;
        $customersAffected = 0;

        $processCustomer = function (Customer $customer) use ($dryRun, $by, &$deleted, &$updated, &$customersAffected) {
            $contacts = CustomerContact::query()
                ->where('customer_id', $customer->id)
                ->orderBy('id')
                ->get();

            if ($contacts->count() <= 1) {
                return;
            }

            $groups = [];
            foreach ($contacts as $contact) {
                $key = $this->dedupeKeyBy($contact, $by);
                $groups[$key] ??= [];
                $groups[$key][] = $contact;
            }

            $localDeleted = 0;
            $localUpdated = 0;

            foreach ($groups as $key => $group) {
                if (count($group) <= 1) {
                    continue;
                }

                usort($group, function (CustomerContact $a, CustomerContact $b) {
                    $aScore = $this->completenessScore($a);
                    $bScore = $this->completenessScore($b);
                    if ($aScore !== $bScore) {
                        return $bScore <=> $aScore;
                    }
                    $aTs = $a->updated_at ? $a->updated_at->getTimestamp() : 0;
                    $bTs = $b->updated_at ? $b->updated_at->getTimestamp() : 0;
                    if ($aTs === $bTs) {
                        return $b->id <=> $a->id;
                    }
                    return $bTs <=> $aTs;
                });

                $keeper = $group[0];
                $others = array_slice($group, 1);

                $changes = [];

                $normalizedEmail = $this->normalizeEmail($keeper->email);
                if ($normalizedEmail !== null && $keeper->email !== $normalizedEmail) {
                    $changes['email'] = $normalizedEmail;
                }

                $normalizedPhone = $this->normalizePhone($keeper->phone);
                if ($normalizedPhone !== null && $keeper->phone !== $normalizedPhone) {
                    $changes['phone'] = $normalizedPhone;
                }

                foreach (['name', 'position', 'phone', 'email'] as $field) {
                    $current = $this->normalizeField($keeper->$field);
                    if ($current !== null && $current !== '') {
                        continue;
                    }

                    foreach ($others as $other) {
                        $value = $this->normalizeField($other->$field);
                        if ($value !== null && $value !== '') {
                            $changes[$field] = $field === 'email' ? strtolower($value) : $value;
                            break;
                        }
                    }
                }

                if (!empty($changes)) {
                    $localUpdated++;
                    if (!$dryRun) {
                        $keeper->fill($changes);
                        $keeper->save();
                    }
                }

                $idsToDelete = array_map(fn (CustomerContact $c) => $c->id, $others);
                $localDeleted += count($idsToDelete);

                if (!$dryRun) {
                    CustomerContact::query()->whereIn('id', $idsToDelete)->delete();
                }
            }

            if ($localDeleted > 0 || $localUpdated > 0) {
                $customersAffected++;
                $deleted += $localDeleted;
                $updated += $localUpdated;

                $this->line(sprintf(
                    '%s: merged=%d, deleted=%d',
                    $customer->code,
                    $localUpdated,
                    $localDeleted
                ));
            }
        };

        if ($customerId || $customerCode) {
            $customer = $query->first();
            if (!$customer) {
                $this->error('Customer not found.');
                return self::FAILURE;
            }
            $processCustomer($customer);
        } else {
            $query->orderBy('id')->chunkById(200, function ($customers) use ($processCustomer) {
                foreach ($customers as $customer) {
                    $processCustomer($customer);
                }
            });
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. customers_affected=%d, merged=%d, deleted=%d, dry_run=%s',
            $customersAffected,
            $updated,
            $deleted,
            $dryRun ? 'true' : 'false'
        ));

        return self::SUCCESS;
    }

    private function dedupeKeyBy(CustomerContact $contact, string $by): string
    {
        if ($by === 'name') {
            $name = $this->normalizeName($contact->name);
            return 'name:' . $name;
        }

        $email = $this->normalizeEmail($contact->email);
        if ($email !== null && $email !== '') {
            return 'email:' . $email;
        }

        $phone = $this->normalizePhone($contact->phone);
        if ($phone !== null && $phone !== '') {
            return 'phone:' . $phone;
        }

        $name = $this->normalizeName($contact->name);
        $pos = strtolower(trim((string) ($contact->position ?? '')));
        return 'name:' . $name . '|pos:' . $pos;
    }

    private function normalizeName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/\s+/', ' ', $name);
        return (string) $name;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        return $email === '' ? null : $email;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        $phone = trim((string) $phone);
        return $phone === '' ? null : $phone;
    }

    private function normalizeField(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    private function completenessScore(CustomerContact $contact): int
    {
        $score = 0;
        if ($this->normalizeField($contact->email) !== null) {
            $score += 2;
        }
        if ($this->normalizeField($contact->phone) !== null) {
            $score += 2;
        }
        if ($this->normalizeField($contact->position) !== null) {
            $score += 1;
        }
        if ($this->normalizeField($contact->name) !== null) {
            $score += 1;
        }
        return $score;
    }
}
