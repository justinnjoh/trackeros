<?php
// Geo library - deals with geo locations

class Geo_Lib {


  public function osgrid_tolatlong ( $easting = nu_ll, $northing = null) {

    /**
    * Convert Ordnance Survey grid ref;erence easting/northing coordinate to (OSGB36) latitude/longitude
    *
    * @param easting/northing to be converted to latitude/longitude
    * @return latitude/longitude (in OSGB36) of supplied grid reference
    */

    // return values are in this array
    $result = array(
   	    'lat' => null,
   	    'lng' => null,
   	  );

    $E = is_null($easting) ? "" : $easting;
    $N = is_null($northing) ? "" : $northing;

    // Airy 1830 major & minor semi-axes
    $a = 6377563.396;
    $b = 6356256.909; 

    // NatGrid scale factor on central meridian
    $F0 = 0.9996012717; 

    // NatGrid true origin
    $lat0 = 49*pi()/180;
    $lon0 = -2*pi()/180; 

    // northing & easting of true origin, metres
    $N0 = -100000;
    $E0 = 400000; 

    // eccentricity squared
    $e2 = 1 - pow($b, 2)/pow($a, 2); // (b*b)/(a*a); 
    $n = ($a - $b)/($a + $b);
    $n2 = pow($n, 2); //n*n, 
    $n3 = pow($n, 3); //n*n*n;

    $lat = $lat0;
    $M = 0;

    do {
      $lat = ($N - $N0 - $M)/($a * $F0) + $lat;

      $Ma = (1 + $n + (5/4)*$n2 + (5/4)*$n3) * ($lat - $lat0);
      //var Mb = (3*n + 3*n*n + (21/8)*n3) * Math.sin(lat-lat0) * Math.cos(lat+lat0);
      $Mb = (3*$n + 3*$n2 + (21/8)*$n3) * sin($lat - $lat0) * cos($lat + $lat0);
      $Mc = ((15/8)*$n2 + (15/8)*$n3) * sin(2*($lat - $lat0)) * cos(2*($lat + $lat0));
      $Md = (35/24)*$n3 * sin(3*($lat - $lat0)) * cos(3*($lat + $lat0));

      $M = $b * $F0 * ($Ma - $Mb + $Mc - $Md);                // meridional arc

    } while ($N - $N0 - $M >= 0.00001);  // ie until < 0.01mm

    $cosLat = cos($lat);
    $sinLat = sin($lat);

    // transverse radius of curvature 
    $nu = $a*$F0/sqrt(1 - $e2*pow($sinLat, 2));

    // meridional radius of curvature 
    $rho = $a*$F0*(1 - $e2)/pow(1 - $e2*$sinLat*$sinLat, 1.5);  
    $eta2 = $nu/$rho - 1;

    $tanLat = tan($lat);
    $tan2lat = $tanLat*$tanLat;
    $tan4lat = $tan2lat*$tan2lat;
    $tan6lat = $tan4lat*$tan2lat;

    $secLat = 1/$cosLat;
    $nu3 = pow($nu, 3); 
    $nu5 = pow($nu, 5);
    $nu7 = pow($nu, 7);

    $VII = $tanLat/(2*$rho*$nu);
    $VIII = $tanLat/(24*$rho*$nu3)*(5 + 3*$tan2lat + $eta2 - 9*$tan2lat*$eta2);
    $IX = $tanLat/(720*$rho*$nu5)*(61 + 90*$tan2lat + 45*$tan4lat);
    $X = $secLat/$nu;
    $XI = $secLat/(6*$nu3)*($nu/$rho + 2*$tan2lat);
    $XII = $secLat/(120*$nu5)*(5 + 28*$tan2lat + 24*$tan4lat);
    $XIIA = $secLat/(5040*$nu7)*(61 + 662*$tan2lat + 1320*$tan4lat + 720*$tan6lat);

    $dE = ($E - $E0);
    $dE2 = $dE*$dE;
    $dE3 = $dE2*$dE;
    $dE4 = $dE2*$dE2;
    $dE5 = $dE3*$dE2;
    $dE6 = $dE4*$dE2;
    $dE7 = $dE5*$dE2;

    $lat = $lat - $VII*$dE2 + $VIII*$dE4 - $IX*$dE6;
    $lon = $lon0 + $X*$dE - $XI*$dE3 + $XII*$dE5 - $XIIA*$dE7;


    // convert radians to degrees
    $lat = ($lat * 180)/pi();
    $lon = ($lon * 180)/pi();

    $result = array(
        'lat' => $lat,
        'lng' => $lon,
      );
    
    //return new LatLon(lat.toDeg(), lon.toDeg());

    return ($result);
  }





}

