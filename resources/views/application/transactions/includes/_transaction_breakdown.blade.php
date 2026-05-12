<div class="mb-8" id="transaction-breakdown">
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack my-2">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700">{{ config('app.name') }} Transfer Fee</span>
        <span class="fs-4">{{ !empty($summary['transferFee']) ? $summary['transferFee'] : 0 }} {{ $sourceCurrency['code'] }}</span>
    </label>

    <div class="separator separator-dashed"></div>
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack my-2">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700">Variable Fee</span>
        <span class="fs-4">{{ !empty($summary['variableFeeText']) ? $summary['variableFeeText'] : 'Unavailable' }}</span>
    </label>

    <div class="separator separator-dashed"></div>
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack my-2">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700"> Fixed Fee</span>
        <span class="fs-4">{{ !empty($summary['fixedFee']) ? $summary['fixedFee'] : 'Unavailable' }}</span>
    </label>

    <div class="separator separator-dashed"></div>
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack my-2">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700">Amount we’ll convert</span>
        <span class="fs-4">{{ !empty($summary['amountToConvert']) ? $summary['amountToConvert'] : 0 }} {{ $sourceCurrency['code'] }}</span>
    </label>

    <div class="separator separator-dashed"></div>
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack my-2">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700">Exchange Rate</span>
        <span class="fs-4">{{ !empty($summary['rate']) ? $summary['rate'] : 0 }}</span>
    </label>

    <div class="separator separator-dashed"></div>
    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
        <span class="form-check-label ms-0 fw-bolder fs-4 text-gray-700">Recipient Gets</span>
        <span class="fs-4 me-3">{{ $targetCurrency['symbol'] }}</span>
        <input type="text" class="text-end form-control form-control-solid fs-1 @error('target_amount') is-invalid @enderror" data-symbol="{{ $targetCurrency['symbol'] }}" id="target-amount-display" value="{{ number_format((float)$targetAmount, 2) }}" style="width: 50% !important" readonly>
        <input type="hidden" name="target_amount" id="target-amount" value="{{ number_format((float)$targetAmount, 2, '.', '') }}">
        <input type="hidden" name="quote_uuid" id="quote-uuid" value="{{ $quote->uuid ?? '' }}">
        <input type="hidden" name="idempotency_key" id="idempotency-key" value="{{ $idempotencyKey ?? (string) \Illuminate\Support\Str::uuid() }}">
    </label>
    <div class="separator separator-dashed"></div>
</div>
