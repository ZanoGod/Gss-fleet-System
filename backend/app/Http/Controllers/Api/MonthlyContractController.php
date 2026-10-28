<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyContractRequest;
use App\Http\Resources\MonthlyContractResource;
use App\Models\Customer;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\MonthlyDriver;
use App\Models\Operator;
use App\Services\MonthlyContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MonthlyContractController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', MonthlyContract::class);
        $query = MonthlyContract::query()->with('customer','car','driver','operator','renewals')
            ->when($request->filled('status') && in_array($request->input('status'), MonthlyContract::STATUSES, true), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('start_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('end_date', '<=', $request->input('date_to')))
            ->when($request->filled('car_id'), fn ($q) => $q->where('car_id', $request->integer('car_id')))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(function ($s) use ($term): void {
                    $s->where('contract_no','like',$term)->orWhere('customer_name','like',$term)->orWhere('customer_phone','like',$term)
                        ->orWhere('operator_name','like',$term)->orWhere('status','like',$term)
                        ->orWhereHas('car',fn($c)=>$c->where('plate_no','like',$term)->orWhere('model_name','like',$term))
                        ->orWhereHas('driver',fn($d)=>$d->where('full_name','like',$term));
                });
            });
        $sort=$request->input('sort','updated_at'); $direction=$request->input('direction','desc');
        if(!in_array($sort,['updated_at','start_date','end_date','contract_no','status','monthly_rate'],true))$sort='updated_at';
        if(!in_array($direction,['asc','desc'],true))$direction='desc';
        return MonthlyContractResource::collection($query->orderBy($sort,$direction)->paginate(min(max((int)$request->input('per_page',15),1),100))->withQueryString());
    }

    public function store(MonthlyContractRequest $request, MonthlyContractService $service)
    {
        Gate::authorize('create', MonthlyContract::class);
        return (new MonthlyContractResource($service->save($request->validated())))->response()->setStatusCode(201);
    }

    public function show(MonthlyContract $monthlyContract)
    {
        Gate::authorize('view',$monthlyContract);
        return new MonthlyContractResource($monthlyContract->load('customer','car','driver','operator','renewalParent','renewals.customer','renewals.car','renewals.driver'));
    }

    public function update(MonthlyContractRequest $request, MonthlyContract $monthlyContract, MonthlyContractService $service)
    {
        Gate::authorize('update',$monthlyContract);
        return new MonthlyContractResource($service->save($request->validated(),$monthlyContract));
    }

    public function destroy(MonthlyContract $monthlyContract, MonthlyContractService $service)
    {
        Gate::authorize('delete',$monthlyContract);
        $service->delete($monthlyContract);
        return response()->noContent();
    }

    public function renew(MonthlyContractRequest $request, MonthlyContract $monthlyContract, MonthlyContractService $service)
    {
        Gate::authorize('update',$monthlyContract);
        return (new MonthlyContractResource($service->renew($monthlyContract,$request->validated())))->response()->setStatusCode(201);
    }

    public function options()
    {
        Gate::authorize('viewAny',MonthlyContract::class);
        return response()->json(['data'=>[
            'customers'=>Customer::query()->orderBy('company_name')->orderBy('guest_name')->get(['id','company_name','guest_name','contact_person','contact_phone','contact_email','customer_address','off_day','service_hour','customer_status']),
            'cars'=>MonthlyCar::query()->orderBy('plate_no')->get(['id','plate_no','model_name','availability_status']),
            'drivers'=>MonthlyDriver::query()->orderBy('full_name')->get(['id','full_name','driver_status']),
            'operators'=>Operator::query()->orderBy('full_name')->get(['id','full_name','operator_status']),
            'statuses'=>MonthlyContract::STATUSES,
        ]]);
    }

    public function summary(Request $request)
    {
        Gate::authorize('viewAny',MonthlyContract::class);
        $query=MonthlyContract::query()->when($request->filled('date_from'),fn($q)=>$q->whereDate('start_date','>=',$request->input('date_from')))->when($request->filled('date_to'),fn($q)=>$q->whereDate('end_date','<=',$request->input('date_to')));
        $counts=[];foreach(MonthlyContract::STATUSES as $status)$counts[$status]=(clone $query)->where('status',$status)->count();
        return response()->json(['data'=>['total'=>(clone $query)->count(),'statuses'=>$counts]]);
    }

    public function exportCsv(Request $request)
    {
        Gate::authorize('viewAny',MonthlyContract::class);
        $rows=$this->filtered($request)->with('car','driver')->orderBy('contract_no')->get();
        return response()->streamDownload(function()use($rows):void{
            $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['Contract No','Customer','Vehicle','Driver','Operator','Start Date','End Date','Status','Monthly Rate','Currency','Insurance Price','Insurance Company']);
            foreach($rows as $contract)fputcsv($out,[$contract->contract_no,$contract->customer_name,$contract->car?->plate_no,$contract->driver?->full_name,$contract->operator_name,$contract->start_date?->format('Y-m-d'),$contract->end_date?->format('Y-m-d'),$contract->status,$contract->monthly_rate,$contract->rental_currency,$contract->insurance_price,$contract->insurance_company]);
            fclose($out);
        },'monthly-contracts-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        return MonthlyContract::query()
            ->when($request->filled('status') && in_array($request->input('status'), MonthlyContract::STATUSES, true),fn($q)=>$q->where('status',$request->input('status')))
            ->when($request->filled('date_from'),fn($q)=>$q->whereDate('start_date','>=',$request->input('date_from')))
            ->when($request->filled('date_to'),fn($q)=>$q->whereDate('end_date','<=',$request->input('date_to')))
            ->when($request->filled('car_id'),fn($q)=>$q->where('car_id',$request->integer('car_id')))
            ->when($request->filled('driver_id'),fn($q)=>$q->where('driver_id',$request->integer('driver_id')))
            ->when($request->filled('customer_id'),fn($q)=>$q->where('customer_id',$request->integer('customer_id')))
            ->when($request->filled('search'),function($q)use($request):void{
                $term='%'.$request->string('search')->toString().'%';
                $q->where(function($s)use($term):void{$s->where('contract_no','like',$term)->orWhere('customer_name','like',$term)->orWhere('customer_phone','like',$term)->orWhere('operator_name','like',$term)->orWhereHas('car',fn($c)=>$c->where('plate_no','like',$term))->orWhereHas('driver',fn($d)=>$d->where('full_name','like',$term));});
            });
    }
}
