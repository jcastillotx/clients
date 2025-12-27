<?php

namespace App\Http\Controllers;

use App\Models\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    /**
     * Display a listing of the requests.
     */
    public function index(): View
    {
        return view('requests.index');
    }

    /**
     * Show the form for creating a new request.
     */
    public function create(): View
    {
        return view('requests.create');
    }

    /**
     * Display the specified request.
     */
    public function show(Request $request): View
    {
        $this->authorizeClientAccess($request);

        $request->load([
            'client',
            'creator',
            'assignee',
            'attachments.uploader',
            'publicComments.user',
            'documents',
        ]);

        return view('requests.show', compact('request'));
    }

    /**
     * Show the form for editing the specified request.
     */
    public function edit(Request $request): View
    {
        $this->authorizeClientAccess($request);

        return view('requests.edit', compact('request'));
    }

    /**
     * Authorize that the current user can access this request.
     */
    protected function authorizeClientAccess(Request $request): void
    {
        $user = auth()->user();

        if ($user->isClient() && $request->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this request.');
        }
    }
}
