import {BaseLineChart} from './BaseLine.js?v=2';

export class UserAgentsChart extends BaseLineChart {
    getSeries() {
        return [
            this.getDaySeries(),
            this.getSingleSeries('User agents', 'red'),
        ];
    }
}
