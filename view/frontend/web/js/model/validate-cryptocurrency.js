define(
    [
        'mage/translate',
        'Magento_Ui/js/model/messageList'
    ],
    function ($t, messageList) {
        'use strict';
        return {
            validate: function () {
                var isValid = false;

                if(!document.getElementById('blockbee')) {
                    isValid = true;
                    return isValid;
                }

                if (document.getElementById("blockbee_payment_cryptocurrency_id").value) {
                    isValid = true;
                }

                if(!document.getElementById("blockbee").checked) {
                    isValid = true;
                }

                if (!isValid) {
                    messageList.addErrorMessage({message: $t('Please select a cryptocurrency.')});
                }

                return isValid;
            }
        }
    }
);
