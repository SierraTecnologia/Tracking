<?php

namespace Tracking\Http\Controllers\Admin\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Providers\Metrics\LogParser;
use Tracking\Models\Metrics\LarametricsModel;
use DB;
use Carbon\Carbon;

class ModelController extends Controller
{
    
    public function index(Request $request): \Illuminate\View\View
    {
        $modelChanges = LarametricsModel::groupBy('model')
            ->select('model', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('model');
            
        $modelsAmounts = array();

        $earliestModel = LarametricsModel::orderBy('created_at', 'desc')
            ->first();

        foreach(\Illuminate\Support\Facades\Config::get('larametrics.modelsWatched') as $model) {
            $modelsAmounts[$model] = array(
                'count' => $model::count(),
                'changes' => isset($modelChanges[$model]) ? $modelChanges[$model]['total'] : 0
            );
        }

        return view(
            'rica.larametrics::models.index', [
            'modelsAmounts' => $modelsAmounts,
            'pageTitle' => 'Database Models',
            'pageSubtitle' => 'Data shown is only by models being watched by Larametrics',
            'watchLength' => $earliestModel ? $earliestModel->created_at->diffInDays(Carbon::now()) : 0
            ]
        );
    }

    public function show($model): \Illuminate\View\View
    {
        if(is_numeric($model)) {
            $larametricsModel = LarametricsModel::find($model);

            $modelPrimaryKey = (new $larametricsModel->model)->getKeyName();

            return view(
                'rica.larametrics::models.show', [
                'model' => $larametricsModel,
                'pageTitle' => $larametricsModel->model,
                'modelPrimaryKey' => $modelPrimaryKey
                ]
            );
        } else {
            $appModel = str_replace('+', '\\', $model);
            
            $models = LarametricsModel::where('model', $appModel)
                ->orderBy('created_at', 'desc')
                ->get();

            $earliestModel = LarametricsModel::orderBy('created_at', 'desc')
                ->first();

            $modelPrimaryKey = (new $appModel)->getKeyName();

            return view(
                'rica.larametrics::models.model', [
                'models' => $models,
                'pageTitle' => $appModel,
                'watchLength' => $earliestModel ? $earliestModel->created_at->diffInDays(Carbon::now()) : 0,
                'modelPrimaryKey' => $modelPrimaryKey
                ]
            );
        }
    }

    public function revert(LarametricsModel $model): \Illuminate\Http\RedirectResponse
    {
        $original = json_decode($model->original, true);
        $revertModel = $model->model::find($original['id']);

        if($model->method === 'created') {

            if($revertModel) {
                $revertModel->delete();
            }

        } elseif($model->method === 'deleted') {

            unset($original['id']);
            $model->model::create($original);

        } else {

            if($revertModel) {
                unset($original['updated_at']);
                $revertModel->update($original);
            }
        
        }

        return redirect()->route('rica.larametrics::models.index');
    }

}
