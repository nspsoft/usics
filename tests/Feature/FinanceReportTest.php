<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Finance\Coa;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createRequiredCoas(): array
    {
        $coas = [];
        $coas['4100'] = Coa::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'Revenue']);
        $coas['5100'] = Coa::create(['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'Expense']);
        $coas['6100'] = Coa::create(['code' => '6100', 'name' => 'Operational Expenses', 'type' => 'Expense']);
        return $coas;
    }

    public function test_profit_and_loss_without_filters_includes_all_transactions(): void
    {
        $coas = $this->createRequiredCoas();

        // Transaction 1: May
        $journal1 = Journal::create([
            'reference' => 'JRN-MAY',
            'date' => '2026-05-15',
            'description' => 'May Revenue',
            'status' => 'posted'
        ]);
        JournalItem::create([
            'journal_id' => $journal1->id,
            'coa_id' => $coas['4100']->id,
            'debit' => 0,
            'credit' => 1000000
        ]);

        // Transaction 2: June
        $journal2 = Journal::create([
            'reference' => 'JRN-JUNE',
            'date' => '2026-06-15',
            'description' => 'June Revenue',
            'status' => 'posted'
        ]);
        JournalItem::create([
            'journal_id' => $journal2->id,
            'coa_id' => $coas['4100']->id,
            'debit' => 0,
            'credit' => 2000000
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('finance.reports'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Reports')
            ->where('pnl.total_revenue', 3000000)
        );
    }

    public function test_profit_and_loss_filters_by_date_range(): void
    {
        $coas = $this->createRequiredCoas();

        // Transaction 1: May
        $journal1 = Journal::create([
            'reference' => 'JRN-MAY',
            'date' => '2026-05-15',
            'description' => 'May Revenue',
            'status' => 'posted'
        ]);
        JournalItem::create([
            'journal_id' => $journal1->id,
            'coa_id' => $coas['4100']->id,
            'debit' => 0,
            'credit' => 1000000
        ]);

        // Transaction 2: June
        $journal2 = Journal::create([
            'reference' => 'JRN-JUNE',
            'date' => '2026-06-15',
            'description' => 'June Revenue',
            'status' => 'posted'
        ]);
        JournalItem::create([
            'journal_id' => $journal2->id,
            'coa_id' => $coas['4100']->id,
            'debit' => 0,
            'credit' => 2000000
        ]);

        // Request with date range matching only June
        $response = $this->actingAs($this->user)
            ->get(route('finance.reports', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30'
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Reports')
            ->where('pnl.total_revenue', 2000000)
            ->where('filters.start_date', '2026-06-01')
            ->where('filters.end_date', '2026-06-30')
        );
    }
}
