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
            $geoData['regions'][$region->CodReg3Car->__toString()] = array(
                'label' => $region->LibReg->__toString()
            );

            if ($keyword == $region->LibReg->__toString()) {
                $result = $region;
            }

            foreach ($region->Departements->Departement as $departement) {
                $geoData['regions'][$region->CodReg3Car->__toString()]['departements'][$departement->CodDpt3Car->__toString()] = array(
                    'label' => $departement->LibDpt->__toString(),
                    'code' => $departement->CodDpt3Car->__toString()
                );

                if ($keyword == $departement->LibDpt->__toString()) {
                    $result = $departement;
                }
                foreach ($departement->Communes->Commune as $commune) {
                    $geoData['regions'][$region->CodReg3Car->__toString()]['departements'][$departement->CodDpt3Car->__toString()]['communes'][$commune->CodSubCom->__toString()] = array(
                        'label' => $commune->LibSubCom->__toString(),
                        'code' => $departement->CodDpt3Car->__toString() . $commune->CodSubCom->__toString()
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.min.js"></script>
    </head>
    <body>
        <div class="container">
            <h1 class="page-header"><?php echo $data['title'] ?> - </h1>

            <div class="col-xs-12">
                <form id="search-form" action="">
                    <div class="col-xs-1" style="margin-right: 15px;">
                        <button type="button" class="btn btn-primary" id="btnFE">Pays entier</button>
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
                        <button type="button" class="btn btn-primary" id="btnRecherche">Rechercher</button>
                    </div>
                </form>
            </div>

            <div class="col-xs-12" style="margin-top:20px;margin-bottom:20px;">
                <div class="col-xs-5">
                    <div id="pieChartContainer">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                <div class="col-xs-7">
                    <div id="barChartContainer">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
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
                            <tbody id="resultatsTBody">
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
        <div class="modal fade" id="modalNoData" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Impossible de trouver les informations</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Les données pour cette zone géographique n'ont pas encore été publiées</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(document).ready( function () {
                var regions = <?php print_r(json_encode($geoData['regions'])); ?>;
                $('#reg').on('change', function () {
                    getAndDisplay();
                    var id = $(this).find(':selected').attr('id').substr(4);
                    $('#dpt').empty();
                    $('#com').empty();

                    var opt = "<option id='dpt_default'>Choisir un département</option>";
                    var opt2 = "<option id='commune_default'>Choisir un département</option>";
                    $('#dpt').append(opt);
                    $('#com').append(opt2);

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
                    getAndDisplay();
                    var id = $(this).find(':selected').attr('id').substr(4);
                    $('#com').empty();
                    var opt = "<option id='commune_default'>Choisir une commune</option>";
                    $('#com').append(opt);
                    if(id && id != "default") {
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

                $('#btnFE').on('click', function () {
                    $('#com option[selected="selected"]').each(
                        function() {
                            $(this).removeAttr('selected');
                        }
                    );
                    $('#com option:first').attr('selected','selected');
                    $('#dpt option[selected="selected"]').each(
                        function() {
                            $(this).removeAttr('selected');
                        }
                    );
                    $('#dpt option:first').attr('selected','selected');
                    $('#reg option[selected="selected"]').each(
                        function() {
                            $(this).removeAttr('selected');
                        }
                    );
                    $('#reg option:first').attr('selected','selected');
                    $('#reg').prop('disabled',false);
                    $('#dpt').prop('disabled',true);
                    $('#com').prop('disabled',true);
                    getAndDisplay();
                });

                $('#btnRecherche').on('click', function () {
                    getAndDisplay();
                });

                $('#com').on('change', function () {
                    getAndDisplay();
                });

                getAndDisplay();
            });
            var baseUrlForT1 = 'http://www.interieur.gouv.fr/avotreservice/elections/telechargements/EssaiPR2017/resultatsT1/';
            var baseUrlForT2 = 'http://www.interieur.gouv.fr/avotreservice/elections/telechargements/EssaiPR2017/resultatsT2/';
        </script>
        <script>
            var changeData = function(url) {
                var context = document.getElementById("pieChart").getContext('2d');
                var pieLabels = ["Abstentions", "Blancs", "Nuls", "Exprimés"];
                var couleursMentions = ["#f14d35", "#67b64d", "#ffd036", "#0075b3"];
                var ctxt = document.getElementById("barChart").getContext('2d');
                var couleursCandidats = ["#67b64d","#f14d35","#553c86","#0075b3","#f66b2d","#219d97","#f12736","#1b4e92","#ffd036","#a6336e","#99a83e"];
                $.ajax({
                    type: "POST",
                    url: 'ajax.php',
                    data: {
                        'action': 'getInfos',
                        'url': url
                    },
                    success: function(data) {
                        var xmlDoc = $.parseXML(data);
                        if (xmlDoc != null && xmlDoc != undefined) {
                            var xml = $(xmlDoc);
                            var pieValues = [xml.find("Abstentions").find("Nombre").text(), xml.find("Blancs").find("Nombre").text(), xml.find("Nuls").find("Nombre").text(), xml.find("Exprimes").find("Nombre").text()];
                            var myDoughnut = new Chart(context, {
                                type: 'pie',
                                data: {
                                    labels: pieLabels,
                                    datasets: [{
                                        backgroundColor: couleursMentions,
                                        borderColor: couleursMentions,
                                        data: pieValues
                                    }]
                                }
                            });
                            var candidatsNames = [];
                            var candidatsScores = [];
                            xml.find("Resultats").find("Candidats").find("Candidat").each(function(index) {
                                candidatsNames.push($(this).find("PrenomPsn").text() + " " + $(this).find("NomPsn").text());
                                candidatsScores.push($(this).find("NbVoix").text());
                                $("#resultatsTBody").append("<tr><td>" + $(this).find("PrenomPsn").text() + " " + $(this).find("NomPsn").text() + "</td><td>" + $(this).find("RapportExprime").text() + "%</td><td>" + $(this).find("NbVoix").text() + "</td></tr>");
                            });
                            var myBarChart = new Chart(ctxt, {
                                type: 'bar',
                                data: {
                                    labels: candidatsNames,
                                    datasets: [{
                                        backgroundColor: couleursCandidats,
                                        data: candidatsScores
                                    }]
                                },
                                options: {
                                    legend: {
                                        display: false
                                    },
                                    tooltips: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                return tooltipItem.yLabel;
                                            }
                                        }
                                    }
                                }
                            });
                        }
                        else {
                            $("#modalNoData").modal("show");
                        }
                    },
                    error: function(err) {
                        $("#modalNoData").modal("show");
                    }
                });
            }
            var getAndDisplay = function() {
                $('#pieChartContainer').empty();
                $('#pieChartContainer').html('<canvas id="pieChart"></canvas>');
                $('#barChartContainer').empty();
                $('#barChartContainer').html('<canvas id="barChart"></canvas>');
                var reg_id = $('#reg').find(":selected").attr("id");
                var dpt_id = $('#dpt').find(":selected").attr("id");
                var com_id = $('#com').find(":selected").attr("id");
                var today = new Date();
                var dateSecondTour = new Date("2017-05-07");
                var baseUrl = dateSecondTour < today ? baseUrlForT2 : baseUrlForT1;
                $("#resultatsTBody").empty();
                if (reg_id == "reg_default"){
                    changeData(baseUrl + "FE.xml");
                } else {
                    if (dpt_id == "dpt_default") {
                        changeData(baseUrl + reg_id.substr(4) + "/" + reg_id.substr(4) + ".xml");
                    }
                    else {
                        if (com_id == "commune_default") {
                            changeData(baseUrl + reg_id.substr(4) + "/" + dpt_id.substr(4) + "/" + dpt_id.substr(4) + ".xml");
                        }
                        else {
                            changeData(baseUrl + reg_id.substr(4) + "/" + dpt_id.substr(4) + "/" + com_id.substr(4) + ".xml");
                        }
                    }
                }
            }
        </script>
    </body>
</html>
