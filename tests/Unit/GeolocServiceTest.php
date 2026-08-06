<?php

namespace Tests\Unit;

use App\Services\GeolocService;
use PHPUnit\Framework\TestCase;

/** Tests de la formule de Haversine (F3). */
class GeolocServiceTest extends TestCase
{
    public function test_distance_golf_sud_vers_hopital_roi_baudouin(): void
    {
        $d = (new GeolocService())->haversine(14.7712, -17.4098, 14.7758, -17.4056);
        $this->assertEqualsWithDelta(0.68, $d, 0.05);
    }

    public function test_distance_nulle_entre_deux_points_identiques(): void
    {
        $this->assertSame(0.0, (new GeolocService())->haversine(14.77, -17.41, 14.77, -17.41));
    }
}
