<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>
<link href="data/template/interkassa/interkassa.css" rel="stylesheet"/>
<button type="button" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal" data-target="#InterkassaModal" style="display: none;">
    Select Payment Method
</button>

<div class="interkasssa" style="text-align: center;">
    <?php if (is_array($this->data['payment_systems']) && !empty($this->data['payment_systems'])){ ?>
    <button type="button" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal" data-target="#InterkassaModal" style="display: none;">
        Select Payment Method
    </button>
    <div id="InterkassaModal" class="ik-modal fade" role="dialog">
        <div class="ik-modal-dialog ik-modal-lg">
            <div class="ik-modal-content" id="plans">
                <div class="container">
                    <h3>
                        1. <?php echo $this->data['lang_select_payment_method']; ?><br>
                        2. <?php echo $this->data['lang_select_currency']; ?><br>
                        3. <?php echo $this->data['lang_press_pay']; ?><br>
                    </h3>

                    <div class="ik-row">
                        <?php foreach($this->data['payment_systems'] as $ps => $info) { ?>
                        <div class="col-sm-3 text-center payment_system">
                            <div class="panel panel-warning panel-pricing">
                                <div class="panel-heading">
                                    <div class="panel-image">
                                        <img src="data/template/interkassa/images/<?php echo $ps; ?>.png"
                                             alt="<?php echo $info['title']; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="radioBtn btn-group">
                                            <?php foreach($info['currency'] as $currency => $currencyAlias){ ?>
                                            <a class="btn btn-primary btn-sm notActive"
                                               data-toggle="fun"
                                               data-title="<?php echo $currencyAlias; ?>"><?php echo $currency; ?></a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-footer">
                                    <a class="btn btn-lg btn-block ik-payment-confirmation"
                                       data-title="<?php echo $ps; ?>"
                                       href="#"><?php echo $this->data['lang_pay_through']; ?><br>
                                        <strong><?php echo $info['title']; ?></strong>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } else { echo $this->data['payment_systems']; } ?>
</div>