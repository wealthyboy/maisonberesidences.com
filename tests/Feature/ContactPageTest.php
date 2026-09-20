<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_contains_reservations_contact_details(): void
    {
        $this->get(route('information.contact'))
            ->assertOk()
            ->assertSee('reservations@maisonberesidences.com')
            ->assertSee('+234 906 500 7079')
            ->assertSee('https://wa.me/2349065007079', false)
            ->assertSee('Maison Be Residences building exterior');
    }
}
