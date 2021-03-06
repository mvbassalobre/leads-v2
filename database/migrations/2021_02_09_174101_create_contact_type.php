<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactType extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('contact_types', function (Blueprint $table) {
			$table->charset = 'utf8mb4';
			$table->collation = 'utf8mb4_unicode_ci';
			$table->engine = 'InnoDB';
			$table->bigIncrements('id');
			$table->string('description');
			$table->unsignedBigInteger('tenant_id')->nullable();
			$table->foreign('tenant_id')
				->references('id')
				->on('tenants');
			$table->softDeletes();
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
		Schema::dropIfExists('contact_types');
	}
}