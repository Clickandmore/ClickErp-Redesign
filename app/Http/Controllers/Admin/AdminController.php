<?php

namespace App\Http\Controllers\admin;

class AdminController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Title';
    protected $actionObj;
    protected $requestObj;


    /**
     * Get content title.
     *
     * @return string
     */
    protected function title()
    {
        return $this->title;
    }


//    public function index(Content $content)
//    {
//        return $content
//            ->title($this->title())
//            ->description($this->description['index'] ?? trans('admin.list'))
//            ->body($this->grid());
//    }

//    /**
//     * Show interface.
//     *
//     * @param mixed   $id
//     * @param Content $content
//     *
//     * @return Content
//     */
//    public function show($id, Content $content)
//    {
//        return $content
//            ->title($this->title())
//            ->description($this->description['show'] ?? trans('admin.show'))
//            ->body($this->detail($id));
//    }
//
//    /**
//     * Edit interface.
//     *
//     * @param mixed   $id
//     * @param Content $content
//     *
//     * @return Content
//     */
//    public function edit($id, Content $content)
//    {
//        return $content
//            ->title($this->title())
//            ->description($this->description['edit'] ?? trans('admin.edit'))
//            ->body($this->form()->edit($id));
//    }
//
//    /**
//     * Create interface.
//     *
//     * @param Content $content
//     *
//     * @return Content
//     */
//    public function create(Content $content)
//    {
//        return $content
//            ->title($this->title())
//            ->description($this->description['create'] ?? trans('admin.create'))
//            ->body($this->form());
//    }
    public function store()
    {
        $data = $this->actionObj->store($this->requestObj->validated());
        return jsonSuccess($data);
    }

    public function update($id)
    {
        $data = $this->actionObj->updateMaid($id, $this->requestObj->validated());
        return jsonSuccess($data);

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->actionObj->delete($id);
        return jsonSuccess();
    }

}
