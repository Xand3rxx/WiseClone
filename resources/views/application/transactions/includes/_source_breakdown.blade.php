<div class="mb-10" id="source-breakdown">
    <div class="row mb-5">
        <div class="col">
            <label class="form-label fw-bolder fs-6 text-gray-700">Amount to be transfered</label>
            <input type="text" class="form-control form-control-solid fs-1 @error('source_amount') is-invalid @enderror" placeholder="0.50" min="0.01" step="0.01" data-symbol="{{ $sourceCurrency['symbol'] }}" name="source_amount" data-max="{{ $sourceCurrencyBalance }}" max="{{ $sourceCurrencyBalance }}" id="source-amount" value="{{ old('source_amount') }}" autocomplete="off">
            @error('source_amount')
                <x-alert :message="$message" />
            @enderror
        </div>
        <div class="col">
            <label class="form-label fw-bolder fs-6 text-gray-700">Source Currency</label>
            <select name="source_currency_id" aria-label="Select a currecncy" data-control="select2" data-placeholder="Select currency" class="form-select form-select-solid fs-1" id="source-currency-id">
                <option value="" selected disabled></option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency['id'] }}" {{ (old('source_currency_id') == $currency['id']) ? 'selected' : '' }} @if($sourceCurrency['id'] == $currency['id']) selected @endif>
                        <b> {{$currency['code'] }}</b>&#160;-&#160;{{ $currency['name'] }}
                    </option>
                @endforeach
            </select>
            @error('source_currency_id')
                <x-alert :message="$message" />
            @enderror
        </div>
    </div>
</div>
