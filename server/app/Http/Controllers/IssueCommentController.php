<?php

namespace App\Http\Controllers;

use App\Models\IssueComment;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IssueCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $comment = new IssueComment;
        $comment->issue_id = $request->get('issue_id');
        $comment->type = $request->get('type');
        $comment->text = $request->get('text');
        $comment->user_logon = Auth::user()->name;
        $comment->save();

        return response()->json($comment);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(IssueComment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(IssueComment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, IssueComment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(IssueComment $comment)
    {
        //
    }
}
