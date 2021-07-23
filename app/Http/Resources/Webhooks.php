<?php

namespace App\Http\Resources;

use marcusvbda\vstack\Resource;
use marcusvbda\vstack\Fields\{
	Card,
	Text,
	Check
};
use Auth;

class Webhooks extends Resource
{
	public $model = \App\Http\Models\Webhook::class;

	public function globallySearchable()
	{
		return false;
	}

	public function label()
	{
		return "Webhooks";
	}

	public function singularLabel()
	{
		return "Webhook";
	}

	public function icon()
	{
		return "el-icon-finished";
	}

	public function search()
	{
		return ["name"];
	}

	public function table()
	{
		$columns = [];
		$columns["code"] = ["label" => "#", "sortable_index" => "id"];
		$columns["label"] = ["label" => "Descrição"];
		$columns["url"] = ["label" => "Url", "sortable_index" => "token"];
		return $columns;
	}

	public function canCreate()
	{
		return  Auth::user()->hasRole(["super-admin", "admin"]);
	}

	public function canUpdate()
	{
		return  Auth::user()->hasRole(["super-admin", "admin"]);
	}

	public function canDelete()
	{
		return  Auth::user()->hasRole(["super-admin", "admin"]);
	}

	public function canImport()
	{
		return false;
	}

	public function canExport()
	{
		return false;
	}

	public function canViewList()
	{
		return  Auth::user()->hasRole(["super-admin", "admin"]);
	}

	public function canView()
	{
		return  Auth::user()->hasRole(["super-admin", "admin"]);
	}

	public function fields()
	{
		$fields = [
			new Text([
				"label" => "Nome",
				"field" => "name",
				"required" => true,
				"rules" => "max:255"
			]),
			new Text([
				"label" => "Token",
				"field" => "token",
				"required" => true,
				"disabled" => true,
				"default" => md5(uniqid()),
				"rules" => "max:255"
			]),
			new Check([
				"label" => "Habilitado",
				"field" => "enabled",
				"default" => true
			])
		];
		$cards = [new Card("Informações Básicas", $fields)];
		return $cards;
	}

	public function afterViewSlot()
	{
		$data = request("content")->requests()->orderBy("id", "desc")->paginate(10);
		$resource = $this;
		return view("admin.webhooks.requests", compact("data", "resource"));
	}
}