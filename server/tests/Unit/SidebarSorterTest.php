<?php

namespace Tests\Unit;

use App\Services\Lexicon\SidebarSorter;
use PHPUnit\Framework\TestCase;

class SidebarSorterTest extends TestCase
{
    public function test_sort_key_ignores_case_diacritics_and_punctuation(): void
    {
        $this->assertSame(SidebarSorter::sortKey('a'), SidebarSorter::sortKey('ā'));
        $this->assertSame(SidebarSorter::sortKey('a'), SidebarSorter::sortKey('A'));
        $this->assertSame(SidebarSorter::sortKey('æ'), SidebarSorter::sortKey('ǣ'));
        $this->assertSame('h₂os', SidebarSorter::sortKey('*h₂ós-'));
        $this->assertSame('bʰer', SidebarSorter::sortKey('*bʰer-'));
    }

    public function test_sort_key_keeps_letters_of_any_script(): void
    {
        $this->assertSame('λογος', SidebarSorter::sortKey('λόγος'));
        $this->assertNotSame('', SidebarSorter::sortKey('דָּבָר'));
    }

    public function test_by_entry_orders_by_normalized_headword(): void
    {
        $items = collect([
            (object) ['entry' => '*bʰer-'],
            (object) ['entry' => 'Ād-'],
            (object) ['entry' => 'ab-'],
        ]);

        $this->assertSame(['ab-', 'Ād-', '*bʰer-'], SidebarSorter::byEntry($items)->pluck('entry')->values()->all());
    }
}
