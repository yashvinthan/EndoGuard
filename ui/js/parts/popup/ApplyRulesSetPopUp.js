import {BasePopUp} from './BasePopUp.js?v=2';

export class ApplyRulesSetPopUp extends BasePopUp {
    constructor() {
        const formParams = {
            mainButtonId:       'apply-rules-set-btn',
            confirmButtonId:    'confirm-apply-rules-set-button',
            formId:             'apply-rules-set-form',
            popupId:            'apply-rules-set-popup',
        };

        super(formParams);

        const onSelectValueChange = this.onSelectValueChange.bind(this);
        this.applyRulesSetSelector.addEventListener('change', onSelectValueChange, false);
    }

    onSelectValueChange(e) {
        this.mainButton.disabled = !this.applyRulesSetSelector.value;
    }

    get applyRulesSetSelector() {
        return document.getElementById('rules-preset');
    }
}
