<?php

namespace App\Http\Controllers;

use App\Http\Models\Lead;
use App\Http\Models\Polo;
use App\Http\Models\Status;
use App\Http\Models\UserNotification;
use App\Http\Models\Webhook;
use App\Http\Models\WebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use marcusvbda\vstack\Services\Messages;
use Carbon\Carbon;
use Tholu\Packer\Packer;

class WebhookController extends Controller
{
	const INDEXES = [
		"name" => ["name", "nome", "first_name", "full_name", "fullname"],
		"email" => ["email", "e-mail"],
		"city" => ["city", "cidade", "Cidade Aberto", "cidade_aberto"],
		"state" => ["state", "estado", "Estado Aberto", "estado_aberto"],
		"phone" => ["personal_phone", "Telefone Pessoal", "Telefone", "phone_number", "phone"],
		"mobile_phone" => ["mobile_phone", "Telefone Movel", "Celular", "cellphone", "cell", "cellphone_number"],
		"source" => ["source", "src", "origem", "extra_source"],
		"conversion_origin" => ["conversion_origin.source", "conversion_identifier"],
	];

	public function handler($token, Request $request, $lead_id = null)
	{
		$webhook = Webhook::where('token', $token)->where("enabled", true)->firstOrFail();
		$createdRequest = $webhook->requests()->create(['content' => $request->all()]);
		$processed = $this->processRequest($webhook, $webhook->settings, $createdRequest, $lead_id);
		if (!$processed) {
			$this->sendNotProcessedRequestNotificationToAdminUsers($webhook);
		}
		return response('OK');
	}

	public function actions($code, $action, Request $request)
	{
		$webhook = Webhook::findByCode($code);
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));
		return $this->{$methodName}($webhook, $request);
	}

	public function setHideValue($webhook, Request $request)
	{
		$request = WebhookRequest::findOrFail($request->row_id);
		$request->hide = $request->hide == 'hide' ? false : true;
		$request->save();
		return ["success" => true];
	}

	protected function destroySettings($webhook, Request $request)
	{
		$setting = $webhook->settings()->findOrFail($request["id"]);
		$setting->delete();
		Messages::send("success", "Configuração removida com sucesso !!");
		return ["success" => true];
	}

	protected function destroyRequests($webhook, Request $request)
	{
		$webhook->requests()->findOrFail($request["id"])->delete();
		Messages::send("success", "Request removido com sucesso !!");
		return ["success" => true];
	}

	private function getSortedObject($obj)
	{
		$newObj = [];
		$value = (array) $obj;
		$keys = array_keys($value);
		sort($keys);
		foreach ($keys as $key) {
			$newObj[$key] = $value[$key];
		}
		return $newObj;
	}

	private function getIndexesObject($values)
	{
		$indexes = [];
		foreach ($values as $key => $value) {
			$indexes[] = $key . "=>" . $value;
		}
		return (implode('|', $indexes));
	}

	public function storeSettings($token, Request $request)
	{
		$webhook = Webhook::where('token', $token)->firstOrFail();
		$data = $request->all();
		$indexes = $this->getIndexesObject($this->getSortedObject($data["value"]));
		$this->makeWebhookSettings($webhook, $indexes, $data["polo_id"]);
		$this->processNotApprovedRequests($webhook);
		Messages::send("success", "Configuração de webhook salva com sucesso !!");
		return ["success" => true];
	}

	private function makeWebhookSettings($webhook, $indexes, $polo_id)
	{
		$setting = $webhook->settings()->firstOrNew([
			'indexes' => $indexes,
		]);
		$setting->polo_id = $polo_id;
		$setting->save();
		$setting->refresh();
		return $setting;
	}

	private function processNotApprovedRequests($webhook)
	{
		$settings = $webhook->settings;
		$requests = $webhook->requests()->where("approved", "=", false)->get();
		foreach ($requests as $request) {
			$this->processRequest($webhook, $settings, $request);
		}
	}

	private function processRequest($webhook, $settings, $request, $lead_id = null)
	{
		foreach ($settings as $setting) {
			$indexes = $setting->indexes;
			$content = $request->content; //content está sendo usado em eval, não remover
			$indexes = explode("|", $indexes);
			$hasPositiveResults = count(array_filter(array_map(function ($row) use ($content) { //content está sendo usado em eval, não remover
				$explodedRow = explode("=>", $row);
				$index = $explodedRow[0];
				$value = $explodedRow[1];
				$complete_index = '@$content' . $index;
				eval('$content_value = ' . $complete_index . ' ? ' . $complete_index . ' : null;');
				return $value === $content_value;
			}, $indexes))) == count($indexes);
			if ($hasPositiveResults) {
				return $this->createLeadWithSetting($request, $webhook, $setting, $lead_id);
			}
		}

		$sources = $this->getSources($request->content, [$webhook->name]);

		if (in_array("direct_script", $sources)) {
			$city = $this->getRequestCity($request);
			$processed_city = mb_convert_encoding(strtolower(preg_replace('/\s+/', '', $city["complete"])), "EUC-JP", "auto");
			try {
				$polo = Polo::whereRaw("(convert(replace(lower(json_unquote(json_extract(data,'$.city'))),' ','') USING ASCII) = '$processed_city' and json_unquote(json_extract(data,'$.head')) = 'false')")->first();
				if ($polo) {
					$only_city = $city['city'];
					$only_state = $city['state'];
					$indexes = "['city']=>$only_city|['state']=>$only_state";
					$setting = $this->makeWebhookSettings($webhook, $indexes, $polo->id);
					return $this->createLeadWithSetting($request, $webhook, $setting, $lead_id);
				}
			} catch (\Exception $e) {
				return false;
			}
		}

		return false;
	}

	private function createLeadWithSetting($request, $webhook, $setting, $lead_id)
	{
		$this->createLead($request, $webhook, $setting, $lead_id);
		$request->approved = true;
		$request->hide = false;
		$request->save();
		return true;
	}

	private function getHelperIndexes()
	{
		$helper_indexes = [
			"",
			"first_conversion.",
			"first_conversion.content.",
			"first_conversion.conversion_origin.",
			"last_conversion.",
			"last_conversion.content.",
			"last_conversion.conversion_origin.",

			"lead_api.",
			"lead_api.first_conversion.",
			"lead_api.first_conversion.content.",
			"lead_api.first_conversion.conversion_origin.",
			"lead_api.last_conversion.",
			"lead_api.last_conversion.content.",
			"lead_api.last_conversion.conversion_origin.",

			"0.",
			"lead_api.0.",
			"lead_api.0.first_conversion.",
			"lead_api.0.first_conversion.content.",
			"lead_api.0.first_conversion.conversion_origin.",
			"lead_api.0.last_conversion.",
			"lead_api.0.last_conversion.content.",
			"lead_api.0.last_conversion.conversion_origin.",

			"lead_api.leads.0.",
			"lead_api.leads.0.first_conversion.",
			"lead_api.leads.0.first_conversion.content.",
			"lead_api.leads.0.first_conversion.conversion_origin.",
			"lead_api.leads.0.last_conversion.",
			"lead_api.leads.0.last_conversion.content.",
			"lead_api.leads.0.last_conversion.conversion_origin.",

			"lead_api.0.leads.",
			"lead_api.0.leads.first_conversion.",
			"lead_api.0.leads.first_conversion.content.",
			"lead_api.0.leads.first_conversion.conversion_origin.",
			"lead_api.0.leads.last_conversion.",
			"lead_api.0.leads.last_conversion.content.",
			"lead_api.0.leads.last_conversion.conversion_origin.",

			"leads.",
			"leads.first_conversion.",
			"leads.first_conversion.content.",
			"leads.first_conversion.conversion_origin.",
			"leads.last_conversion.",
			"leads.last_conversion.content.",
			"leads.last_conversion.conversion_origin.",

			"leads.0.",
			"leads.0.first_conversion.",
			"leads.0.first_conversion.content.",
			"leads.0.first_conversion.conversion_origin.",
			"leads.0.last_conversion.",
			"leads.0.last_conversion.content.",
			"leads.0.last_conversion.conversion_origin.",
		];
		return $helper_indexes;
	}

	private function getLeadInfo($content, $indexes = [], $fallback = null)
	{
		$content = Obj2Array($content);
		foreach ($indexes as $index) {
			foreach ($this->getHelperIndexes() as $helper_index) {
				$value = Arr::get((@$content ? $content : []), $helper_index . $index, null);
				if ($value) {
					return $value;
				}
			}
		}
		return $fallback;
	}

	public function getSources($content, $extra_tags = [])
	{
		$tags = [];
		$source = $this->getLeadInfo($content, static::INDEXES["source"]);
		if ($source) {
			$tags[] = $source;
		}
		$source = $this->getLeadInfo($content, static::INDEXES["conversion_origin"]);
		if ($source) {
			$tags[] = $source;
		}
		$tags = array_unique(array_merge($tags, $extra_tags));
		$tags = array_filter($tags, function ($row) {
			return $row != "unknown";
		});
		return $tags;
	}

	private function getRequestCity($request)
	{
		$city = $this->getLeadInfo($request->content, static::INDEXES["city"], 'Cidade não informada');
		$state = $this->getLeadInfo($request->content, static::INDEXES["state"], 'Estado não informado');
		return [
			"city" => $city,
			"state" => $state,
			"complete" => $city . " - " . $state,
		];
	}

	private function createLead($request, $webhook, $setting, $lead_id = null)
	{
		$now = Carbon::now();
		$status = Status::value("waiting");
		$name = $this->getLeadInfo($request->content, static::INDEXES["name"]);
		$email = $this->getLeadInfo($request->content, static::INDEXES["email"]);
		$sources = $this->getSources($request->content, [$webhook->name]);

		if (!$lead_id) {
			$foundedLead = Lead::where('data->name', $name)->where('data->email', $email)->where("status_id", $status->id)->first();
			$lead = $foundedLead ? $foundedLead : new Lead;
		} else {
			$lead = Lead::findOrFail($lead_id);
		}

		$lead->polo_id = $setting->polo_id;
		$lead->tenant_id = $webhook->tenant_id;
		$lead->webhook_id = $webhook->id;
		$lead->webhook_request_id = $request->id;
		$conversions = [];
		if (@$lead->id) {
			$lead->status_id = $status->id;
			$conversions = Lead::logConversions($lead, $now, $webhook, null, "Converteu automáticamente sem alteração de status", true);
		}
		$comment = @$lead->comment ?? '';
		$obs = @$lead->obs ?? 'via Webhook ( ' . $webhook->name . ' )';

		$mobile_phone = $this->getLeadInfo($request->content, static::INDEXES["mobile_phone"]);
		$phone = $this->getLeadInfo($request->content, static::INDEXES["phone"]);
		$city = $this->getRequestCity($request);

		$lead->data = [
			"lead_api" => $request->content,
			"city" => $city["complete"],
			"email" => $email,
			"name" => $name,
			"phones" => [$mobile_phone, $phone],
			"obs" => $obs,
			"comment" => $comment,
			"source" => $sources
		];
		$lead->conversions = $conversions;
		$lead->save();
	}

	private function sendNotProcessedRequestNotificationToAdminUsers($webhook)
	{
		$polos = $webhook->tenant->polos()->where('data->head', true)->get();
		foreach ($polos as $polo) {
			UserNotification::create([
				"polo_id" => $polo->id,
				"data" => [
					"message" => "Novo request sem configuração de direcionamento de lead no webhook <b>" . $webhook->name . "</b>",
					"icon" => "el-icon-finished",
					"url" => "/admin/webhooks/" . $webhook->code
				]
			]);
		}
	}

	public function eventsHandler(Request $request)
	{
		$this->validate($request, [
			"integration_key" => "required|string",
			"event" => "required|string"
		]);
		return response('OK');
	}

	public function scriptDirectRequest($token)
	{
		$webhook = Webhook::where('token', $token)->where("enabled", true)->firstOrFail();
		$path = public_path("assets/js/webhook_script.js");
		$script_content = file_get_contents($path);

		$script_content = preg_replace('/\_WEBHOOK_URL_\b/', $webhook->url, $script_content);
		$script_content = preg_replace('/\_WEBHOOK_NAME_\b/', $webhook->name, $script_content);
		$script_content = str_replace("/*", "", $script_content);
		$script_content = str_replace("*/", "", $script_content);

		$packer = new  Packer($script_content, 'Normal', true, false, true);
		$script_content = $packer->pack();

		return response($script_content)->header('Content-Type', 'application/javascript');
	}
}
