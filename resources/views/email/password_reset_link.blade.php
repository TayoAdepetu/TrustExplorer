@extends('layouts.email')
@section('content')
<tr>
  <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
      <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
          <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">Hi {{$body['first_name']}},</p>
          <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
            <b>A password reset for the account associated with this email has been requested.
              <br />Please reset your password by clicking the button below.</b>

            <br />
            If you did not request for a password reset, please ignore this message.
          </p>
          <table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box;">
            <tbody>
              <tr>
                <td align="left" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
                  <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                    <tbody>
                      <tr>
                        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #008952; border-radius: 5px; text-align: center;">
                          <a href="{{$body['password_reset_link']}}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #008952; border: solid 1px #008952; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #008952;">
                            Reset Password
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </tbody>
          </table>
          <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
            <b>Thank you,<br />Team TrustExplorer</b>
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection