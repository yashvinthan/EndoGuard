export class ThresholdsForm {
    constructor() {
        this.blacklistMax = 98;
        this.reviewQueueMax = 99;

        const updateReviewQueueOptions = this.updateReviewQueueOptions.bind(this);
        this.reviewQueueInput.addEventListener('input', updateReviewQueueOptions, false);

        const updateBlacklistOptions = this.updateBlacklistOptions.bind(this);
        this.blacklistInput.addEventListener('input', updateBlacklistOptions, false);
    }

    updateReviewQueueOptions(e) {
        const value = this.reviewQueueVal;
        this.blacklistInput.max = (value <= this.blacklistMax + 1) ? value - 1 : this.blacklistMax;
    }

    updateBlacklistOptions(e) {
        const value = this.blacklistVal;
        this.reviewQueueInput.min = (value <= this.reviewQueueMax - 1) ? value + 1 : this.reviewQueueMax;
    }


    get reviewQueueInput() {
        return document.querySelector('input[name="review-queue-threshold"]');
    }

    get blacklistInput() {
        return document.querySelector('input[name="blacklist-threshold"]');
    }

    get reviewQueueVal() {
        return parseInt(document.querySelector('input[name="review-queue-threshold"]').value || 99, 10);
    }

    get blacklistVal() {
        return parseInt(document.querySelector('input[name="blacklist-threshold"]').value || -1, 10);
    }
}
