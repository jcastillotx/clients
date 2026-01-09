<?php

namespace App\Http\Livewire\Admin\MaintenancePlans;

use App\Models\Client;
use App\Models\MaintenancePlan;
use Livewire\Component;

class MaintenancePlanEdit extends Component
{
    public MaintenancePlan $plan;

    public ?int $clientId = null;

    public string $name = '';

    public string $description = '';

    public string $status = 'active';

    public ?float $monthlyRate = null;

    public int $includedHours = 0;

    public ?float $hourlyRateOverage = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    protected function rules(): array
    {
        return [
            'clientId' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,paused,expired,cancelled'],
            'monthlyRate' => ['nullable', 'numeric', 'min:0'],
            'includedHours' => ['required', 'integer', 'min:0'],
            'hourlyRateOverage' => ['nullable', 'numeric', 'min:0'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ];
    }

    public function mount(MaintenancePlan $plan): void
    {
        $this->plan = $plan->load(['client', 'supportTickets']);
        $this->clientId = $plan->client_id;
        $this->name = $plan->name;
        $this->description = $plan->description ?? '';
        $this->status = $plan->status;
        $this->monthlyRate = $plan->monthly_rate;
        $this->includedHours = $plan->included_hours;
        $this->hourlyRateOverage = $plan->hourly_rate_overage;
        $this->startDate = $plan->start_date?->format('Y-m-d');
        $this->endDate = $plan->end_date?->format('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        $this->plan->update([
            'client_id' => $this->clientId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'monthly_rate' => $this->monthlyRate,
            'included_hours' => $this->includedHours,
            'hourly_rate_overage' => $this->hourlyRateOverage,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        session()->flash('success', 'Maintenance plan updated successfully.');

        return redirect()->route('admin.maintenance-plans.index');
    }

    public function delete()
    {
        $this->plan->delete();

        session()->flash('success', 'Maintenance plan deleted.');

        return redirect()->route('admin.maintenance-plans.index');
    }

    public function render()
    {
        return view('livewire.admin.maintenance-plans.edit', [
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => config('client-portal.maintenance_plan_statuses', []),
        ]);
    }
}
