<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3Dセキュア動作確認 - 支払い情報入力</title>
  <script src="https://js.pay.jp/v2/pay.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous">
  </script>
</head>

<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <h1 class="h2 text-center">支払い情報入力</h1>

        <div id="token-error" class="alert alert-danger p-2 my-3" role="alert"></div>
        <div class="my-4">
          <label for="number-form" class="form-label mb-0">カード番号</label>
          <div id="number-form" class="form-control border border-secondary-subtle my-2"></div>
          <label for="expiry-form" class="form-label mb-0">有効期限</label>
          <div id="expiry-form" class="form-control border border-secondary-subtle my-2"></div>
          <label for="cvc-form" class="form-label mb-0">セキュリティコード</label>
          <div id="cvc-form" class="form-control border border-secondary-subtle my-2"></div>
          <label for="name-form" class="form-label mb-0">カード名義</label>
          <input id="name-form" type="text" class="form-control border border-secondary-subtle my-2"></input>
          <label for="email-form" class="form-label mb-0">メールアドレス</label>
          <input id="email-form" type="text" class="form-control border border-secondary-subtle my-2"></input>
        </div>

        <form name="payment" action="/payment" method="POST">
          @csrf
          <input id="token" name="cardToken" type="hidden"></input>
          <div class="text-center">
            <button type="button" class="btn btn-primary" onclick="onSubmit(event)">決済する</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const payjp = Payjp("{{ config('pay.public_key')}}")

    // カード情報入力欄を生成
    const elements = payjp.elements()
    const numberElement = elements.create("cardNumber")
    numberElement.mount("#number-form")
    const expiryElement = elements.create("cardExpiry")
    expiryElement.mount("#expiry-form")
    const cvcElement = elements.create("cardCvc")
    cvcElement.mount("#cvc-form")

    // エラーメッセージ表示
    const showError = (message) => {
      document.querySelector("#token-error").style.display = "block"
      document.querySelector("#token-error").innerText = message
    }
    // エラーメッセージ非表示
    const hideError = () => {
      document.querySelector("#token-error").style.display = "none"
    }

    // 初期表示時にサーバーで設定されたエラーメッセージ表示（設定されてなければ非表示）
    const errorMessage = "{{ session('errorMessage') ?? '' }}"
    if (errorMessage === "") {
      hideError()
    } else {
      showError(errorMessage)
    }

    // 決済するボタン押下時イベント
    const onSubmit = async () => {
      hideError()
      const response = await payjp.createToken(numberElement, {
        card: {
          name: document.getElementById("name-form").value,
          email: document.getElementById("email-form").value,
        },
      })
      if (response.error) {
        showError(response.error.message)
        return
      }
      document.querySelector("#token").value = response.id
      document.payment.submit()
    }
  </script>
</body>

</html>
