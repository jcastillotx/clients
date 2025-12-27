<?php

namespace App\Http\Livewire\Feedback;

use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TestimonialManager extends Component
{
    public function approve(int $id, bool $public = false): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        $t = Testimonial::query()->findOrFail($id);
        $t->update([
            'is_approved' => true,
            'is_public' => $public,
        ]);
        session()->flash('success', 'Testimonial approved.');
    }

    public function reject(int $id): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        $t = Testimonial::query()->findOrFail($id);
        $t->update([
            'is_approved' => false,
            'is_public' => false,
        ]);
        session()->flash('success', 'Testimonial updated.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $pending = Testimonial::query()->with('client')->where('is_approved', false)->orderByDesc('id')->limit(200)->get();
        $approved = Testimonial::query()->with('client')->where('is_approved', true)->orderByDesc('id')->limit(200)->get();

        return view('livewire.feedback.testimonial-manager', [
            'pending' => $pending,
            'approved' => $approved,
        ]);
    }
}

