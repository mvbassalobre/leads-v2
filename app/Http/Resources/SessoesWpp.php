<?php

namespace App\Http\Resources;

use App\Http\Controllers\WppMessagesController;
use App\Http\Models\WppMessage;
use marcusvbda\vstack\Resource;
use marcusvbda\vstack\Fields\{
	Card,
	CustomComponent,
	Text,
};
use Auth;
use App\Http\Models\WppSession;
use marcusvbda\vstack\Services\Messages;

class SessoesWpp extends Resource
{
	public $model = WppSession::class;
	public function globallySearchable()
	{
		return false;
	}

	public function label()
	{
		return "Sessões WhatsApp";
	}

	public function singularLabel()
	{
		return "Sessão WhatsApp";
	}

	public function icon()
	{
		return "el-icon-s-promotion";
	}

	public function search()
	{
		return ["name"];
	}

	public function lenses()
	{
		return [
			"Sem Mensagens" => ["field" => "with_messages", "value" => 'without', 'handler' => function ($q) {
				$q->whereDoesntHave('wpp_messages');
			}],
			"com Mensagens" => ["field" => "with_messages", "value" => 'with', 'handler' => function ($q) {
				$q->whereHas('wpp_messages');
			}],
		];
	}

	public function table()
	{
		$columns = [];
		$columns["code"] = ["label" => "Código", "sortable_index" => "id"];
		$columns["label"] = ["label" => "Nome"];
		// $columns["status_check"] = ["label" => "Status da Sessão", "sortable" => false];
		$columns["qty_messages"] = ["label" => "Qtde de mensagens", "sortable" => false];
		$columns["f_created_at_badge"] = ["label" => "Data", "sortable_index" => "created_at"];
		return $columns;
	}

	private function canAccessModule()
	{
		return Auth::user()->canAccessModule("whatsapp");
	}

	public function canViewList()
	{
		return  $this->canAccessModule() && hasPermissionTo('viewlist-wppsession');
	}

	public function canView()
	{
		return  false;
	}

	public function canCreate()
	{
		return  $this->canAccessModule() && hasPermissionTo('create-wppsession');
	}

	public function canClone()
	{
		return  false;
	}

	public function canUpdate()
	{
		return true;
		return  false;
	}

	public function canDelete()
	{
		return  $this->canAccessModule() && hasPermissionTo('destroy-wppsession');
	}

	public function canDeleteRow($row)
	{
		return  hasPermissionTo('destroy-wppsession');
	}

	public function destroyMethod($content)
	{
		(new WppMessagesController())->deleteSession($content);
		WppMessage::where("id", $content->wpp_messages->where("status", "!=", "sent")->pluck("id")->toArray())->delete();
		$result = Parent::destroyMethod($content);
		return $result;
	}

	public function canImport()
	{
		return false;
	}

	public function canExport()
	{
		return false;
	}

	public function fields()
	{
		$fields = [];
		$fields[] = new Text([
			"label" => "Nome",
			"field" => "name",
			"rules" => ["required", "max:255"],
			"description" => "Nome da Sessão"
		]);
		$cards[] = new Card("Identificação", $fields);

		$fields = [];
		// $is_creating = $this->isCreating();
		// // if ($is_creating) {
		$fields[] = new CustomComponent("<InputQrCode :form='form' :data='data' :errors='errors' field_index='auth' />", [
			"label" => "Código QR",
			"field" => "auth",
			"description" => "Escaneie o código QR",
			"_uid" => "qrcode",
		]);
		// }
		$fields[] = new Text([
			"label" => "Token",
			"description" =>  "Escaneie o código QR para obter o token",
			"field" => "string_token",
			"rules" => ["required"],
			"disabled" => true,
			"rows" => 10,
		]);
		$cards[] = new Card("Autenticação", $fields, [
			"_uid" => "auth_card"
		]);
		return $cards;
	}

	private function isCreating()
	{
		return request()->page_type == 'create';
	}

	public function secondCrudBtn()
	{
		return false;
	}

	public function storeMethod($id, $data)
	{
		$target = @$id ? $this->getModelInstance()->findOrFail($id) : $this->getModelInstance();
		$target->name  = data_get($data, "data.name");
		$model_data = [
			"token" =>  data_get($data, "data.string_token")
		];
		$target->data = $model_data;
		$target->save();
		Messages::send("success", "Registro salvo com sucesso !!");
		$route = route('resource.index', ["resource" => $this->id]);
		return ["success" => true, "route" => $route, "model" => $target];
	}
}
