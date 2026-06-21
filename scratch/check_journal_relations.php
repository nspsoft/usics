<?php
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;

echo "Checking journals & items in database...\n";

$distinctJournalIds = JournalItem::select('journal_id')->distinct()->pluck('journal_id')->toArray();
echo "Distinct journal_ids in journal_items: " . implode(', ', $distinctJournalIds) . "\n";

$journalsInDb = Journal::whereIn('id', $distinctJournalIds)->get(['id', 'number', 'date'])->toArray();
echo "Matching journals in database: " . json_encode($journalsInDb) . "\n";

$firstItem = JournalItem::first();
echo "First journal item attributes: " . json_encode($firstItem ? $firstItem->toArray() : null) . "\n";

// Let's check table schemas
echo "\nChecking schema of journals table:\n";
$journalsSchema = DB::select("DESCRIBE journals");
foreach ($journalsSchema as $col) {
    echo "{$col->Field} - {$col->Type} - Null: {$col->Null}\n";
}

echo "\nChecking schema of journal_items table:\n";
$itemsSchema = DB::select("DESCRIBE journal_items");
foreach ($itemsSchema as $col) {
    echo "{$col->Field} - {$col->Type} - Null: {$col->Null}\n";
}
