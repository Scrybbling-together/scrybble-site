<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemarkableDocumentShareRequest;
use App\Models\RemarkableDocumentShare;
use Illuminate\Support\Facades\Auth;

class ReMarkableDocumentFeedbackController extends Controller
{
    //    public function index()
    //    {
    //        return RemarkableDocumentShare::all();
    //    }
    //
    public function store(RemarkableDocumentShareRequest $request)
    {
        $arr = array_merge($request->validated(), ['user_id' => Auth::id()]);

        return RemarkableDocumentShare::create($arr);
    }

    //    public function show(RemarkableDocumentShare $remarkableDocumentShare)
    //    {
    //        return $remarkableDocumentShare;
    //    }
    //
    //    public function update(RemarkableDocumentShareRequest $request, RemarkableDocumentShare $remarkableDocumentShare)
    //    {
    //        $remarkableDocumentShare->update($request->validated());
    //
    //        return $remarkableDocumentShare;
    //    }
    //
    //    public function destroy(RemarkableDocumentShare $remarkableDocumentShare)
    //    {
    //        $remarkableDocumentShare->delete();
    //
    //        return response()->json();
    //    }
}
