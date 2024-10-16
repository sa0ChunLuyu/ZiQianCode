<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->comment('名称');
            $table->string('admin_auths', 1000)->comment('权限IDS JSON');
            $table->string('desc', 100)->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('1-可用 2-禁用');
            $table->tinyInteger('del')->default(2)->comment('1-删除 2-正常');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_groups');
    }
};
