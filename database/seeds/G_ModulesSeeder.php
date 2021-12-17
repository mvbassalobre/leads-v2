<?php

use Illuminate\Database\Seeder;
use App\Http\Models\{
	Module,
	Polo,
	Tenant,
	RelationModule
};

class G_ModulesSeeder extends Seeder
{
	public function run()
	{
		DB::statement('SET AUTOCOMMIT=0;');
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		$this->createModules();
		$this->createModuleRelations();
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		DB::statement('SET AUTOCOMMIT=1;');
		DB::statement('COMMIT;');
	}

	private function createModules()
	{
		Module::truncate();
		Module::create([
			"name" => "Whatsapp Sender",
			"ref" => "whatsapp-sender",
		]);
	}

	private function createModuleRelations()
	{
		RelationModule::truncate();
		$this->createWppModuleRelations();
	}

	private function createWppModuleRelations()
	{
		$wpp = Module::where("ref", "whatsapp-sender")->first();
		RelationModule::create([
			"model_type" => Tenant::class,
			"model_id" => 1,
			"enabled" => true,
			"module_id" => $wpp->id,
		]);

		RelationModule::create([
			"model_type" => Polo::class,
			"model_id" => 2, //sede
			"enabled" => true,
			"module_id" => $wpp->id,
		]);
	}
}
