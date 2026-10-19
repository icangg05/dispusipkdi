<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Akun saya')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function simpanProfil(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'Tulis nama Anda.',
            'email.required' => 'Tulis alamat email.',
            'email.email' => 'Periksa lagi alamat email ini.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        $user->update($data);
        $this->dispatch('toast', message: 'Profil akun disimpan.');
    }

    public function simpanSandi(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Tulis kata sandi saat ini.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Tulis kata sandi baru.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan sama persis.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('toast', message: 'Kata sandi diganti.');
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Akun saya</h1>
            <p>Nama dan email untuk masuk ke panel admin, serta kata sandi.</p>
        </div>
    </div>

    <div class="pnl-grid pnl-grid--2">
        <form class="pnl-card" wire:submit="simpanProfil">
            <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="user-round" :size="18" /> Profil</h2></div>
            <div class="pnl-card__body pnl-form">
                <x-ad.field label="Nama" for="name" error="name"><input id="name" class="pnl-input" wire:model="name" autocomplete="name"></x-ad.field>
                <x-ad.field label="Email" for="email" error="email"><input id="email" type="email" class="pnl-input" wire:model="email" autocomplete="email"></x-ad.field>
            </div>
            <div class="pnl-card__foot"><span></span><x-dk.button type="submit" variant="primary" icon="save">Simpan profil</x-dk.button></div>
        </form>

        <form class="pnl-card" wire:submit="simpanSandi">
            <div class="pnl-card__head"><h2 class="pnl-card__title"><x-dk.icon name="lock" :size="18" /> Kata sandi</h2></div>
            <div class="pnl-card__body pnl-form">
                <x-ad.field label="Kata sandi saat ini" for="current_password" error="current_password"><input id="current_password" type="password" class="pnl-input" wire:model="current_password" autocomplete="current-password"></x-ad.field>
                <x-ad.field label="Kata sandi baru" for="password" error="password" help="Minimal 8 karakter."><input id="password" type="password" class="pnl-input" wire:model="password" autocomplete="new-password"></x-ad.field>
                <x-ad.field label="Ulangi kata sandi baru" for="password_confirmation"><input id="password_confirmation" type="password" class="pnl-input" wire:model="password_confirmation" autocomplete="new-password"></x-ad.field>
            </div>
            <div class="pnl-card__foot"><span></span><x-dk.button type="submit" variant="primary" icon="save">Ganti kata sandi</x-dk.button></div>
        </form>
    </div>
</div>
