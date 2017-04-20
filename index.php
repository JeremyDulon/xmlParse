<?php
/**
 * Created by PhpStorm.
 * User: jerem
 * Date: 19/04/2017
 * Time: 14:59
 */

function parseXmlFromUrl($url) {
    $xmlstr = file_get_contents($url);

    $xml = new SimpleXMLElement($xmlstr);

    return json_decode(json_encode($xml, JSON_UNESCAPED_UNICODE), true);
}

function getData($url) {
    $xml = parseXmlFromUrl($url);

    $data = array();

    $data['title'] = 'Résultats de la ' . $xml['Scrutin']['Type'] . ' ' . $xml['Scrutin']['Annee'];

    return $data;
}

function search($keyword = null) {
    $xml = simplexml_load_file('listeregdptcom.xml');

    $geoData = array();
    $result = null;

    if($xml->Scrutin->Type != 'Présidentielle' || $xml->Scrutin->Annee != '2017') {
        echo 'Pas la bonne année pélo';
    } else {
        foreach ($xml->Regions->Region as $region) {
            $geoData['regions'][$region->CodReg->__toString()] = array(
                'label' => $region->LibReg->__toString()
            );

            if ($keyword == $region->LibReg->__toString()) {
                $result = $region;
            }

            foreach ($region->Departements->Departement as $departement) {
                $geoData['regions'][$region->CodReg->__toString()]['departements'][$departement->CodMinDpt->__toString()] = array(
                    'label' => $departement->LibDpt->__toString(),
                    'code' => $departement->CodMinDpt->__toString()
                );

                if ($keyword == $departement->LibDpt->__toString()) {
                    $result = $departement;
                }
                foreach ($departement->Communes->Commune as $commune) {
                    $geoData['regions'][$region->CodReg->__toString()]['departements'][$departement->CodMinDpt->__toString()]['communes'][$commune->CodSubCom->__toString()] = array(
                        'label' => $commune->LibSubCom->__toString(),
                        'code' => $departement->CodMinDpt->__toString() . $commune->CodSubCom->__toString()
                    );

                    if ($keyword == $commune->LibSubCom->__toString()) {
                        $result = array(
                            'com' => $commune,
                            'dpt' => $departement->LibDpt->__toString(),
                            'reg' => $region->LibReg->__toString()
                        );
                    }
                }
            }
        }
    }

    $geoData['result'] = $result;

    return $geoData;
}

if(isset($_GET) && !empty($_GET)) {
    $regName = $_GET['reg'];
    $dptName = $_GET['dpt'];
    $comName = $_GET['com'];

    if(preg_match('/^Choisir un/',$comName) == 0) {
        $geoData = search($comName);
    } else if (preg_match('/^Choisir un/',$dptName) == 0) {
        $geoData = search($dptName);
    } else if (preg_match('/^Choisir un/',$regName) < 0) {
        $geoData = search($regName);
    } else {
        $geoData = search();
    }
} else {
    $geoData = search();
}
$data = getData('http://www.interieur.gouv.fr/avotreservice/elections/telechargements/EssaiPR2017/resultatsT1/032/002/002001.xml');
?>

<html>
    <head>
        <title><?php echo $data['title'] ?></title>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
<!--        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">-->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">
            <h1 class="page-header"><?php echo $data['title'] ?> - </h1>

            <div class="col-xs-12">
                <form id="search-form" action="">
                    <div class="col-xs-1" style="margin-right: 15px;">
                        <button type="button" class="btn btn-primary">Pays entier</button>
                    </div>
                    <div class="form-group col-xs-3">
                        <select class="form-control" name="reg" id="reg">
                            <option id="reg_default">Choisir une région</option>
                            <?php foreach ($geoData['regions'] as $key => $region) {$label = $region['label'];echo "<option id='reg_$key'>$label</option>";} ?>
                        </select>
                    </div>

                    <div class="form-group col-xs-3">
                        <select class="form-control" name="dpt" id="dpt" disabled="disabled">
                            <option id="dpt_default">Choisir une région</option>
                            <!--                     --><?php //foreach ($geoData['departements'] as $departement) {$label = $departement['label'];echo "<option>$label</option>";} ?>
                        </select>
                    </div>

                    <div class="form-group col-xs-3">
                        <select class="form-control" id="com" name="com" disabled="disabled">
                            <option id="commune_default">Choisir une région</option>
                            <!--                    --><?php //foreach ($geoData['communes'] as $commune) {$label = $commune['label'];echo "<option>$label</option>";} ?>
                        </select>
                    </div>
                    <div class="col-xs-1">
                        <button type="submit" class="btn btn-primary">Rechercher</button>
                    </div>
                </form>
            </div>


            <div class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Résultats</div>

                    <!-- Table -->
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nom du candidat</th>
                                    <th>Pourcentage de voix</th>
                                    <th>Nombre de votants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Jean Roger</td>
                                    <td>12,2%</td>
                                    <td>546654</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(document).ready( function () {
                var regions = <?php print_r(json_encode($geoData['regions'])); ?>;
                $('#reg').on('change', function () {
                    var id = $(this).find(':selected').attr('id').substr(4);
                    $('#dpt').empty();
                    $('#com').empty();

                    var opt = "<option>Choisir un département</option>";
                    $('#dpt').append(opt);
                    $('#com').append(opt);

                    if(id != undefined && id != 'default') {
                        var departements = regions[id];

                        $.each(departements.departements, function () {
                            var opt = "<option id='dpt_"+this.code+"'>"+this.label+"</option>";
                            $('#dpt').append(opt);
                        });

                        $('#dpt').prop('disabled',false);
                    } else {
                        $('#dpt option:first-child').html("Choisir une région");
                        $('#com option:first-child').html("Choisir une région");
                        $('#dpt').prop('disabled',true);
                        $('#com').prop('disabled',true);
                    }
                });

                $('#dpt').on('change', function () {
                    var id = $(this).find(':selected').attr('id').substr(4);
                    $('#com').empty();
                    var opt = "<option>Choisir une commune</option>";
                    $('#com').append(opt);
                    if(id) {
                        var reg_id = $('#reg').find(':selected').attr('id').substr(4);
                        var communes = regions[reg_id].departements[id];

                        $.each(communes.communes, function () {
                            opt = "<option id='com_"+this.code+"'>"+this.label+"</option>";
                            $('#com').append(opt);
                        });

                        $('#com').prop('disabled',false);
                    } else {
                        $('#com').prop('disabled',true);
                    }
                });

//                $('#search-form').on('submit', function() {
//                    var $inputs = $('#search-form :input');
//                    $.each($inputs, function () {
//                       console.log(this);
//                    });
//                })
            });
        </script>
    </body>
</html>
