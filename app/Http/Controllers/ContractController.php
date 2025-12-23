<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    /**
     * Display a listing of the contracts.
     */
    public function index(): View
    {
        return view('contracts.index');
    }

    /**
     * Display the specified contract.
     */
    public function show(Contract $contract): View
    {
        $this->authorizeClientAccess($contract);

        $contract->load('client');

        return view('contracts.show', compact('contract'));
    }

    /**
     * Download the contract file.
     */
    public function download(Contract $contract): StreamedResponse
    {
        $this->authorizeClientAccess($contract);

        if (!$contract->file_path || !Storage::disk('contracts')->exists($contract->file_path)) {
            abort(404, 'Contract file not found.');
        }

        ActivityLog::log(
            "Downloaded contract: {$contract->title}",
            $contract,
            null,
            'downloaded',
            'contracts'
        );

        return Storage::disk('contracts')->download(
            $contract->file_path,
            $contract->title . '.pdf'
        );
    }

    /**
     * Sign the contract.
     */
    public function sign(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeClientAccess($contract);

        if (!$contract->isPendingSignature()) {
            return back()->with('error', 'This contract cannot be signed.');
        }

        $validated = $request->validate([
            'signature' => 'required|string',
            'agree_terms' => 'required|accepted',
        ]);

        $contract->sign(
            auth()->user()->name,
            $request->ip(),
            $validated['signature']
        );

        ActivityLog::log(
            "Signed contract: {$contract->title}",
            $contract,
            ['signed_by' => auth()->user()->name],
            'signed',
            'contracts'
        );

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', 'Contract signed successfully!');
    }

    /**
     * Authorize that the current user can access this contract.
     */
    protected function authorizeClientAccess(Contract $contract): void
    {
        $user = auth()->user();

        if ($user->isClient() && $contract->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this contract.');
        }
    }
}
