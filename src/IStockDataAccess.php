<?php
interface IStockDataAccess
{
    public function insertPriceData($symbol, $priceData);
    public function insertTechnicalIndicator($symbol, $indicatorData);
    public function insertCandlestickPattern($symbol, $patternData);
    // ... add other relevant methods as needed
}
