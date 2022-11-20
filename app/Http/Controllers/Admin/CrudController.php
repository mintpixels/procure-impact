<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Auth;
use \DB;

class CrudController extends Controller
{
    protected $model;
    protected $fields = [];
    protected $orderBy = 'name';
    protected $orderDirection = 'ASC';
    protected $listView = '';
    protected $singleView = '';
    protected $searchFields = ['name'];
    protected $baseRoute = '';
    protected $skipBlank = [];
    protected $entityName = '';
    protected $pageSize = 1000;
    protected $filter = 'filter';
    protected $filterPageSize = 10000;

    /**
     * Custom handler for saving fields.
     */
    protected function saveFields($request, &$entity) {}

    /**
     * Custom handler for adding variables for the view.
     */
    protected function addViewFields(&$params, $entity) {}

    /**
     * Show the list of available entities;
     */
    public function list(Request $request)
    {
        $entities = $this->model::orderBy($this->orderBy, $this->orderDirection)->take($this->pageSize);

        if($request->search) 
        {
            $words = array_map('trim', explode(' ', $request->search));
            $field = $this->searchFields[0];
            foreach($words as $word)
                $entities->where($field, 'like', '%' . $word . '%');
        }

        if($request->has($this->filter) && $request->input($this->filter))
        {
            $entities->where($this->filter, $request->input($this->filter));
            $this->pageSize = $this->filterPageSize;
        }

        return view($this->listView)->with([
            'entities' => $entities->limit($this->pageSize)->get()
        ]);
    }

    /**
     * Show a specific entity or the page to create a new one.
     */
    public function show($id = false)
    {
        $entity = $this->model::find($id);
        if(!$entity) $entity = new $this->model;

        $fields = [$this->entityName => $entity];
        $this->addViewFields($fields, $entity);

        return view($this->singleView)->with($fields);
    }

    /**
     * Create or save changes to a entity.
     */
    public function save(Request $request, $id)
    {
        $entity = $this->model::find($id);

        if(!$entity) 
            $entity = new $this->model;

        foreach($this->fields as $field) {
            if($request->has($field)) {
                if(!in_array($field, $this->skipBlank) || $request->input($field))
                    $entity->$field = $request->input($field);
            }
        }

        // Default to the name if the label isn't specified.
        if(\Schema::hasColumn($entity->getTable(), 'label') &&
           \Schema::hasColumn($entity->getTable(), 'name') &&
           $entity->label == '') {

            $entity->label = $entity->name;
        }
        
        // Save any custom fields.
        $this->saveFields($request, $entity);            
        $entity->save();

        return redirect($this->baseRoute . '/' . $entity->id)->with([
            'status' => 'Your changes have been saved'
        ]);
    }

    /**
     * Delete a saved entity.
     */
    public function delete($id)
    {
        $this->model::where('id', $id)->delete();

        return response()->json([]);
    }

    /**
     * Clone the entity.
     */
    public function copy($id)
    {
        $entity = $this->model::where('id', $id)->first();
        $clone = $entity->clone();

        return response()->json([
            'id' => $clone->id
        ]);
    }
}