<div {{ $attributes }}>
    <div class="card">
        <div class="card-header">
            <span class="fs-4">Connect with reMarkable <span class="fs-5 text-muted">(step 2/2)</span></span>
        </div>
        <div class="card-body">
            <p><a target="_blank" href="https://my.remarkable.com/device/desktop/connect">Retrieve your one-time-code</a> and fill it in below</p>
            <form method="POST" action="/onetimecode">
                @csrf
                <div class="input-group">
                    <input class="input-group-text" required minlength=8 maxlength=8 pattern="[a-z]{8}"
                           placeholder="aabbccdd" name="code" type="text">
                    <input class="btn btn-primary" type="submit" value="submit">
                </div>
            </form>
        </div>
    </div>
</div>
