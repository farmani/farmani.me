<?php
/* @var $this SiteController */
/* @var $model ContactForm */
/* @var $form TbActiveForm */

$this->pageTitle=Yii::app()->name . ' - Contact Us';
$this->breadcrumbs=array(
	'Contact',
);
?>
<div class="container">

    <h1>Contact Us</h1>

    <?php if(Yii::app()->user->hasFlash('contact')): ?>

    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('contact'); ?>
    </div>

    <?php else: ?>

    <p>
    If you have business inquiries or other questions, please fill out the following form to contact us. Thank you.
    </p>

        <?php $form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
                'layout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            )); ?>

        <fieldset>

            <legend>Legend</legend>

        <p class="note">Fields with <span class="required">*</span> are required.</p>

        <?php echo $form->errorSummary($model); ?>

            <?php echo $form->textFieldControlGroup($model, 'name'); ?>
            <?php echo $form->textFieldControlGroup($model, 'email'); ?>
            <?php echo $form->textFieldControlGroup($model, 'subject'); ?>
            <?php echo $form->textAreaControlGroup($model, 'body'); ?>

            <?php if(CCaptcha::checkRequirements()): ?>
            <div class="control-group">
                <label for="yw1" class="control-label"></label>
                <div class="controls"><?php $this->widget('CCaptcha'); ?></div>
            </div>
                <?php echo $form->textFieldControlGroup($model, 'verifyCode',
                    array('help','Please enter the letters as they are shown in the image above. Letters are not case-sensitive.'));
                ?>
            <?php endif; ?>

        </fieldset>

        <?php echo TbHtml::formActions(array(
                TbHtml::submitButton('Submit', array('color' => TbHtml::BUTTON_COLOR_PRIMARY)),
                TbHtml::resetButton('Reset',array()),
            )); ?>

    <?php $this->endWidget(); ?>

    </div><!-- form -->

    <?php endif; ?>
</div>