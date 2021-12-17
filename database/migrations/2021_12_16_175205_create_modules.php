<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string("name");
            $table->string("ref");
            $table->jsonb("data");
            $table->timestamps();
        });

        Schema::create('relation_modules', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->boolean("enabled")->default(false);
            $table->string("model_type");
            $table->integer("model_id");
            $table->unsignedBigInteger('module_id');
            $table->foreign('module_id')
                ->references('id')
                ->on('modules');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('relation_modules');
        Schema::dropIfExists('modules');
    }
}
