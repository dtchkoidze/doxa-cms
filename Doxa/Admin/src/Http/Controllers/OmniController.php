<?php

namespace Doxa\Admin\Http\Controllers;

use Illuminate\View\View;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Doxa\Admin\Libraries\OmniPackageConfigurator;


class OmniController extends Controller
{
    use OmniPackageConfigurator;

    protected string $module;

    protected string $method;

    protected string $view;

    protected $repository;

    protected $datagridClass;

    protected $channels;

    protected $package;

    public function temporary_test() {

        // dump([
        //     'auth' => auth(),
        //     'middleware' => Route::getCurrentRoute()->middleware(),
        // ]);

        return 'temporary_test';
    }

    public function index()
    {

        if(!Auth::check()) {
            Auth::logout();
            return redirect()->route('admin.login');
        }

        $this->configure('index');

        $params = [
            'module' => $this->module,
            'title' => omniModuleTrans($this->module, 'index', 'title'),
            'method' => 'index',
            'mode' => 'datagrid',
        ];

        return view($this->view, $params);
    }

    public function create(): View
    {
        $this->configure('create');

        return view(
            $this->view,
            [
                'id' => 0,
                'module' => $this->module,
                'method' => 'create',
                'mode' => 'edit',
            ]
        );
    }

    public function edit(): View
    {
        $this->configure('edit');

        // $item = $this->repository->getEditedItem(request('id'));
        // $data = $this->repository->getActualData();
        // dd($item, $data);

        if ($this->repository->checkItemExists(request('id'))) {

            return view(
                $this->view,
                [
                    'id' => request('id'),
                    'module' => $this->module,
                    'method' => 'edit',
                    'mode' => 'edit',
                ]
            );
        } else {
            abort('404');
        }
    }

    public function copy(): View
    {
        $this->configure('copy');

        if ($this->repository->checkItemExists(request('id'))) {

            return view(
                $this->view,
                [
                    'id' => request('id'),
                    'module' => $this->module,
                    'method' => 'copy',
                    'mode' => 'edit',
                    'copied' => true
                ]
            );
        } else {
            abort('404');
        }
    }

    public function getDatagrid(): JsonResponse
    {

        // dump(user_can('edit'));


        $this->configure('index');

        //----------------------------------------

        $dataGrid = app($this->datagridClass);

        $configuration = [
            'module' => $this->module,
            'package' => $this->package,
        ];
        // if (method_exists($this->repository, 'getModel')) {
        //     $configuration['model'] = $this->repository->getModel();
        // }
        $dataGrid->configure($configuration);

        //dd($dataGrid->getData());

        $set = [
            'datagrid' => $dataGrid->getData(),
        ];

        if (request('init') == true) {
            //Clog::write('test_ol', 'init == true');
            // dd($this->package->canCreate());
            $set = array_merge($set, [
                'permissions' => [
                    'create' => $this->package->canCreate(),
                ],
                'routes' => [
                    'create' => route('admin.' . $this->module . '.create'),
                ],
                'channels' => Chlo::altAsAssocById(without_channels: $this->package->isChannelsignored()),
                //'ch_lo' => Chlo::asAssocById(without_channels: $this->package->isChannelsignored()),
                'vocab' => [
                    'title' => omniModuleTrans($this->module, 'index', 'title'),
                    'create-button' => omniModuleTrans($this->module, 'create-btn'),
                    'toolbar.results' => omniModuleTrans('default', 'datagrid.toolbar.results'),
                    'toolbar.search.title' => omniModuleTrans('default', 'datagrid.toolbar.search.title'),
                    'toolbar.filters.title' => omniModuleTrans('default', 'datagrid.toolbar.filters.title'),
                    'toolbar.per-page' => omniModuleTrans('default', 'datagrid.toolbar.per-page'),
                    'toolbar.of' => omniModuleTrans('default', 'datagrid.toolbar.of'),
                    'toolbar.mass-actions.select-action' => omniModuleTrans('default', 'datagrid.toolbar.mass-actions.select-action'),

                    'actions' => omniModuleTrans('default', 'datagrid.actions'),
                    'no-records-found' => omniModuleTrans('default', 'datagrid.no-records-found'),
                    'filters.title' => omniModuleTrans('default', 'datagrid.filters.title'),

                    'actions.disagree-btn' => omniTrans('default', 'actions.disagree-btn'),
                    'actions.agree-btn' => omniTrans('default', 'actions.agree-btn'),

                    'filters.custom-filters.clear-all' => omniTrans('default', 'datagrid.filters.custom-filters.clear-all'),
                    'filters.select' => omniTrans('default', 'datagrid.filters.select'),

                ]
            ]);
        }

        return response()->json(collect($set));
    }

    public function getItemSet(): JsonResponse
    {
        $this->configure('edit');

        $id = request('id');

        //dump($this->package);

        if ($id != 0 && !$this->repository->checkItemExists($id)) {
            return response()->json(collect([
                'success' => false,
                'notification' => [
                    'type' => 'error',
                    'message' => omniModuleTrans($this->module, 'record-not-found')
                ]
            ]));
        }

        if(request('current_channel_id')){
            //dump("request('current_channel_id'): ".request('current_channel_id'));
            Chlo::set(channel: request('current_channel_id'));
        }

        if(request('current_locale_id')){
            //dump("request('current_locale_id'): ".request('current_locale_id'));
            Chlo::set(locale: request('current_locale_id'));
        }

        $current_channel_id = 0;
        if(!$this->package->isChannelsIgnored()){
            $current_channel_id = Chlo::getCurrentChannelId();
            if(!$current_channel_id){
                Chlo::setDefaultChannelAsCurrent();
                $current_channel_id = Chlo::getCurrentChannelId();
            }
        }

        $current_locale_id = 0;
        if($this->package->hasVariations()){
            $current_locale_id = Chlo::getCurrentLocaleId();
            if(!$current_locale_id){
                Chlo::setDefaultLocaleAsCurrent();
                $current_locale_id = Chlo::getCurrentLocaleId();
            }
        }

        $history = false;
        if ($id != 0) {

            $item = $this->repository->getEditedItem($id);
            $data = $this->repository->getActualData();

            // if ($this->package->isSimplePackage()) {
            //     $item = $this->repository->getEditedItem($id);
            //     $data = $this->repository->getActualData();
            //     if($this->package->isHistoryEnabled()){
            //         $history = $this->repository->getHistory();
            //     }
            // } else {
            //     $obj = $this->repository->extractItem($id);
            //     $obj->resolveImages();
            //     $obj->resolveRelations();
            //     $data = $obj->getActualData();
            //     $item = $obj->_get();
            // }
        } else {
            $item = '';
            $data = $this->repository->getActualData();
        }

        $set = [
            'success' => true,

            'id' => $id,

            'module' => $this->module,
            'package' => $this->package,
            'channels' => Chlo::altAsAssocById(without_channels: $this->package->isChannelsignored()),
            'has_variations' => $this->package->hasVariations(),

            'current_channel_id' => $current_channel_id,
            'current_locale_id' => $current_locale_id,

            'item' => $item,
            'data' => $data,

            'history' => $history,

            'route' => route('admin.' . $this->module . '.update', ['id' => 0]),
            'formMethod' => 'PUT',
            'method' => 'edit',

            'csrf_token' => csrf_token(),

            'vocab' => [
                'title' => omniModuleTrans($this->module, request('method'), 'title'),
                'back' => [
                    'link' => route('admin.' . $this->module . '.index'),
                    'title' => omniModuleTrans($this->module, 'back-to-index-btn'),
                ],
                'save' => omniModuleTrans('default', 'save'),
                'save_and_exit' => omniModuleTrans('default', 'save_and_exit'),
                'cancel' => omniModuleTrans('default', 'cancel'),

                'admin_last_update' => omniModuleTrans($this->module, 'last_update'),
                'no_related_records' => omniModuleTrans('default', 'no-related-records'),
                'edit' => omniModuleTrans('default', 'edit'),
                'add' => omniModuleTrans('default', 'add'),
                'done' => omniModuleTrans('default', 'done'),
                'filters.custom-filters.clear-all' => omniTrans('default', 'datagrid.filters.custom-filters.clear-all'),
                'filters.select' => omniTrans('default', 'datagrid.filters.select'),
                'related-already-applied' => omniModuleTrans('default', 'related-already-applied'),
                'related-limit-exceeded' => omniModuleTrans($this->module, 'related-limit-exceeded'),
            ]
        ];

        return response()->json(collect($set));
    }

    public function rowAction()
    {
        $this->configure('index');

        //dd(request('method'), request('data'));
        $re = $this->repository->{request('method')}(request('data'));
        if ($re) {
            if (is_array($re)) {
                $re['type'] = (!empty($re['error'])) ? 'error' : 'success';
                return new JsonResponse($re);
            } else {
                return new JsonResponse(['type' => 'success', 'message' => omniModuleTrans($this->module, 'data-updated')]);
            }
        } else {
            return new JsonResponse(['type' => 'error', 'message' => omniModuleTrans($this->module, 'unknown-error')]);
        }
    }

    public function delete(): JsonResponse
    {
        $this->configure('index');

        try {
            $this->repository->manager->base()->delete(request('id'));
            return new JsonResponse(['message' => omniModuleTrans($this->module, 'delete-success')]);
        } catch (\Exception $e) {
            return new JsonResponse(['type' => 'error', 'message' => omniModuleTrans($this->module, 'delete-fail')]);
        }
    }

    public function deleteVariation(): JsonResponse
    {
        $this->configure('index');
        try {
            $this->repository->manager->variation()->clearVariation(request('id'));
            return new JsonResponse(['message' => omniModuleTrans($this->module, 'variation-delete-success')]);
        } catch (\Exception $e) {
            return new JsonResponse(['type' => 'error', 'message' => omniModuleTrans($this->module, 'no-resource')]);
        }
    }

    public function massAction()
    {
        //dd(request()->all());
        $this->configure('index');
        try {
            $method = 'mass_' . request('action');
            $this->$method(request('indices'));
            return new JsonResponse(['type' => 'success', 'message' => omniModuleTrans($this->module, 'records-deleted')], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['type' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function mass_clearVariations($ids)
    {
        $this->repository->mm->clearVariations($ids, request('channel_id'), request('locale_id'));
    }

    private function mass_delete($ids)
    {
        if($this->package->hasVariations()){
            $this->repository->mm->deleteVariationsByBaseIds($ids);
        }
        $this->repository->qb->whereIn('id', $ids)->delete();
    }

    public function getRelationList()
    {
        $this->configure('index');

        $mm = $this->repository->mm;

        $mm->where('active', 1);
        $list = $mm->getRelationList();

        $re = [
            'list' => $list,
        ];

        return new JsonResponse($re);

    }

    public function store()
    {
        // dd(request()->all());
        $this->configure('index');

        //dump(request()->all());


        $id = request('id');

        $result = $this->repository->mm->save(request()->all(), $id, 'store');
        if (is_int($result)) {
            $id = $result;
            $this->repository->afterSave(['id' => $result]);
        }

        if (!empty(request('current_channel_id'))) {
            session()->put('current_channel_id', request('current_channel_id'));
        }

        if (!empty(request('current_locale_id'))) {
            session()->put('current_locale_id', request('current_locale_id'));
        }

        if (is_int($result)) {
            return response()->json(collect([
                'success' => true,
                'result' => $result,
                'notification' => [
                    'type' => 'success',
                    'message' => omniModuleTrans($this->module, 'save-success')
                ]
            ]));
        } else {

            $result = collect($result->errors());

            if (!empty($result['alert'])) {
                $message = $result['alert'][0];
            } else {
                $message = omniModuleTrans($this->module, 'save-fail');
            }

            return response()->json(collect([
                'success' => false,
                'result' => $result,
                'notification' => [
                    'type' => 'error',
                    'message' => $message
                ]
            ]));
        }
    }

    public function update($edit_id)
    {


        // dd(request()->all());
        $this->configure('index');

        //dump(request()->all());


        //$id = request('id');

        $result = $this->repository->mm->save(request()->all(), $edit_id, 'update');
        if(request('id') != $edit_id){
            $id = request('id');
        } else {
            $id = $edit_id;
        }
        if (is_int($result)) {
            $this->repository->afterSave(['id' => $id]);
        }

        if (!empty(request('current_channel_id'))) {
            session()->put('current_channel_id', request('current_channel_id'));
        }

        if (!empty(request('current_locale_id'))) {
            session()->put('current_locale_id', request('current_locale_id'));
        }

        //$result = $this->repository->save(request()->all(), $id);

        if (is_int($result)) {
            return response()->json(collect([
                'success' => true,
                'result' => $id,
                'notification' => [
                    'type' => 'success',
                    'message' => omniModuleTrans($this->module, 'save-success')
                ]
            ]));
        } else {

            $result = collect($result->errors());

            if (!empty($result['alert'])) {
                $message = $result['alert'][0];
            } else {
                $message = omniModuleTrans($this->module, 'save-fail');
            }

            return response()->json(collect([
                'success' => false,
                'result' => $result,
                'notification' => [
                    'type' => 'error',
                    'message' => $message
                ]
            ]));
        }
    }

    public function savePositions()
    {
        $this->configure('index');
        $this->repository->mm->updatePositions(request());
        return new JsonResponse(['type' => 'success', 'message' => omniModuleTrans($this->module, 'positions-updated')]);
    }
 
}
