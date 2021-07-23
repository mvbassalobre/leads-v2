<?php

namespace App\Http\Controllers;

use App\Http\Models\Status;
use Illuminate\Http\Request;
use DB;
use Auth;

class DashboardController extends Controller
{
	private $divisor = 5;

	public function index()
	{
		return view('admin.index');
	}

	private function getDateRange($index)
	{
		return @(new DatesController)->getRanges()[$index];
	}

	public function getData($action, Request $request)
	{
		return $this->{$action}($request);
	}

	protected function polosQty()
	{
		$data = DB::select("select count(*) as qty from polos where tenant_id = :tenant_id", ["tenant_id" => Auth::user()->tenant_id]);
		return response()->json($data[0]->qty);
	}

	protected function departmentesQty()
	{
		$data = DB::select("select count(*) as qty from departments where tenant_id = :tenant_id", ["tenant_id" => Auth::user()->tenant_id]);
		return response()->json($data[0]->qty);
	}

	protected function usersQty()
	{
		$data = DB::select("select count(*) as qty from users where tenant_id = :tenant_id", ["tenant_id" => Auth::user()->tenant_id]);
		return response()->json($data[0]->qty);
	}

	protected function newLeadsQty(Request $request)
	{
		$data = DB::select("select count(*) as qty from leads where tenant_id = :tenant_id and DATE(created_at) = DATE(:created_at)", [
			"tenant_id" => Auth::user()->tenant_id,
			"created_at" => $request["today"]
		]);
		return response()->json($data[0]->qty);
	}

	private function makeParameters(Request $request)
	{
		$filter_date = $this->getDateRange($request["selected_range"]);
		return [
			"tenant_id" => Auth::user()->tenant_id,
			"start_date" => $filter_date[0],
			"end_date" => $filter_date[1],
			"polo_ids" => implode(",", $request["polo_ids"]),
		];
	}

	protected function getLeadsData(Request $request)
	{
		$parameters = $this->makeParameters($request);
		$data = DB::select(
			"select count(*) as qty from leads where tenant_id = :tenant_id and 
			( DATE(created_at) >= DATE(:start_date) and DATE(created_at) <= DATE(:end_date))
			and polo_id in (:polo_ids)
			",
			$parameters
		);
		return response()->json($data[0]->qty);
	}

	protected function getLeadFinishedData(Request $request)
	{
		$finished = Status::value("finished")->id;
		$parameters = array_merge($this->makeParameters($request), ["finished_status_id" => $finished]);
		$data = DB::select(
			"select count(*) as qty from leads where tenant_id = :tenant_id and 
			( DATE(finished_at) >= DATE(:start_date) and DATE(finished_at) <= DATE(:end_date))
			and polo_id in (:polo_ids)
			and status_id = :finished_status_id",
			$parameters
		);
		return response()->json($data[0]->qty);
	}

	protected function getRankingDepartments(Request $request)
	{
		$finished = Status::value("finished")->id;
		$parameters = array_merge($this->makeParameters($request), ["finished_status_id" => $finished]);
		$data = DB::select(
			"select ifnull(departments.name,'Sem departamento') as department,count(*) as qty FROM 
			leads left join departments on departments.id=leads.department_id where leads.tenant_id = :tenant_id
			and ( DATE(leads.finished_at) >= DATE(:start_date) and DATE(leads.finished_at) <= DATE(:end_date))
			and leads.polo_id in (:polo_ids)
			and leads.status_id = :finished_status_id
			group by leads.department_id order by qty desc
			limit 5",
			$parameters
		);
		return response()->json($data);
	}

	protected function getRankingUsers(Request $request)
	{
		$finished = Status::value("finished")->id;
		$parameters = array_merge($this->makeParameters($request), ["finished_status_id" => $finished]);
		$data = DB::select(
			"select ifnull(users.name,'Sem Responsável') as user,count(*) as qty FROM 
			leads left join users on users.id=leads.responsible_id where leads.tenant_id = :tenant_id
			and ( DATE(leads.finished_at) >= DATE(:start_date) and DATE(leads.finished_at) <= DATE(:end_date))
			and leads.polo_id in (:polo_ids)
			and leads.status_id = :finished_status_id
			group by leads.responsible_id order by qty desc
			limit 5",
			$parameters
		);
		return response()->json($data);
	}

	protected function getCanceledTax(Request $request)
	{
		$parameters = $this->makeParameters($request);
		$data = DB::select(
			"select if(statuses.value = 'canceled','canceled','other') as status, count(*) as qty 
			from leads join statuses on leads.status_id = statuses.id  
			where leads.tenant_id = :tenant_id
			and ( DATE(leads.created_at) >= DATE(:start_date) and DATE(leads.created_at) <= DATE(:end_date))
			and leads.polo_id in (:polo_ids)
			group by status
			ORDER BY qty DESC",
			$parameters
		);
		return response()->json($data);
	}

	protected function getFinishedTax(Request $request)
	{
		$parameters = $this->makeParameters($request);
		$data = DB::select(
			"select if(statuses.value = 'finished','finished','other') as status, count(*) as qty 
			from leads join statuses on leads.status_id = statuses.id  
			where leads.tenant_id = :tenant_id
			and ( DATE(leads.created_at) >= DATE(:start_date) and DATE(leads.created_at) <= DATE(:end_date))
			and leads.polo_id in (:polo_ids)
			group by status
			ORDER BY qty DESC",
			$parameters
		);
		return response()->json($data);
	}
}