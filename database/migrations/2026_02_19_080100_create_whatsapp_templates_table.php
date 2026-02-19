<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('general');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default templates
        \DB::table('whatsapp_templates')->insert([
            [
                'name' => 'Konfirmasi Pesanan',
                'category' => 'sales',
                'body' => "Terima kasih atas pesanan Anda. Pesanan sedang kami proses dan akan kami informasikan perkembangannya.\n\nAda yang bisa kami bantu lagi?",
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Info Pembayaran',
                'category' => 'finance',
                'body' => "Berikut informasi pembayaran:\n\n🏦 Bank: BCA\n💳 No.Rek: 123-456-789\n👤 a.n. PT SPINDO Tbk\n\nMohon konfirmasi setelah transfer. Terima kasih!",
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jam Operasional',
                'category' => 'general',
                'body' => "Jam operasional kami:\n📅 Senin - Jumat: 08.00 - 17.00 WIB\n📅 Sabtu: 08.00 - 12.00 WIB\n📅 Minggu & Hari Libur: Tutup\n\nDi luar jam kerja, pesan Anda akan kami balas besok pagi. 🙏",
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Terima Kasih',
                'category' => 'general',
                'body' => "Terima kasih telah menghubungi kami! 🙏\n\nJika ada pertanyaan lain, jangan ragu untuk menghubungi kami kembali.\n\nSalam hangat,\nTeam CS",
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
