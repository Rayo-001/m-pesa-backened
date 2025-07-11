const express = require('express');
const axios = require('axios');
const app = express();
app.use(express.json());

const consumerKey = process.env.CONSUMER_KEY || 'YOUR_CONSUMER_KEY';
const consumerSecret = process.env.CONSUMER_SECRET || 'YOUR_CONSUMER_SECRET';
const shortcode = process.env.SHORTCODE || '600638';
const passkey = process.env.PASSKEY || 'YOUR_PASSKEY';
const lipaNaMpesaUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

let accessToken = '';

async function getAccessToken() {
  const auth = Buffer.from(consumerKey + ':' + consumerSecret).toString('base64');
  try {
    const response = await axios.get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', {
      headers: { Authorization: 'Basic ' + auth }
    });
    accessToken = response.data.access_token;
    console.log('Access token:', accessToken);
  } catch (error) {
    console.error('Error getting access token:', error.message);
  }
}

app.post('/stkpush', async (req, res) => {
  const { phone, amount } = req.body;
  if (!phone || !amount) {
    return res.status(400).json({ error: 'phone and amount are required' });
  }

  const timestamp = new Date().toISOString().replace(/[^0-9]/g, '').slice(0, 14);
  const password = Buffer.from(shortcode + passkey + timestamp).toString('base64');

  try {
    if (!accessToken) await getAccessToken();

    const stkPayload = {
      BusinessShortCode: shortcode,
      Password: password,
      Timestamp: timestamp,
      TransactionType: 'CustomerPayBillOnline',
      Amount: amount,
      PartyA: phone,
      PartyB: shortcode,
      PhoneNumber: phone,
      CallBackURL: 'https://yourdomain.com/api/callback',
      AccountReference: 'NewFlexShop',
      TransactionDesc: 'Payment for goods'
    };

    const response = await axios.post(lipaNaMpesaUrl, stkPayload, {
      headers: { Authorization: 'Bearer ' + accessToken }
    });

    res.json(response.data);
  } catch (error) {
    console.error('STK Push error:', error.message);
    res.status(500).json({ error: error.message });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log('Server running on port', PORT));
