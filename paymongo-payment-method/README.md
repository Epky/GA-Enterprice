# PayMongo Webhook Integration with Ngrok

## 🚀 Quick Setup Guide

### Step 1: Start Ngrok
```bash
# For Windows
start_ngrok.bat

# For Linux/Mac
./start_ngrok.sh

# Or manually
ngrok http 80
```

### Step 2: Configure PayMongo Webhook
1. Copy your ngrok URL (e.g., `https://abc123.ngrok-free.app`)
2. Go to [PayMongo Dashboard](https://dashboard.paymongo.com) → Settings → Webhooks
3. Update webhook URL to: `https://your-ngrok-url.ngrok-free.app/rentmate-system/paymongo-payment-method/paymongo_webhook.php`
4. Enable these events:
   - `payment.paid`
   - `payment.succeeded`
   - `source.chargeable`
   - `payment.awaiting_action`
   - `payment.failed`

### Step 3: Test Integration
1. Visit: [Ngrok Setup Guide](ngrok_setup_guide.php)
2. Visit: [Webhook Test](webhook_ngrok_test.php)
3. Make a test payment
4. Check staff rental view for automatic transaction ID detection

## 📁 Files Overview

- `paymongo_webhook.php` - Main webhook handler (✅ WORKING)
- `ngrok_setup_guide.php` - Complete setup guide
- `webhook_ngrok_test.php` - Test webhook integration
- `start_ngrok.bat/.sh` - Quick ngrok startup scripts

## ✅ Expected Results

After proper setup:
- ✅ Transaction IDs automatically appear in staff rental view
- ✅ Payment status updates automatically
- ✅ No manual transaction ID entry needed
- ✅ Real-time payment notifications

## 🔧 Troubleshooting

1. **No transaction IDs showing up?**
   - Check if ngrok is running
   - Verify PayMongo webhook URL is correct
   - Check webhook logs for errors

2. **Webhooks not being received?**
   - Ensure ngrok tunnel is active
   - Verify PayMongo webhook events are enabled
   - Check firewall settings

3. **Local testing only?**
   - Use ngrok to expose your local server
   - PayMongo cannot send webhooks to localhost

## 📊 Monitoring

- Check webhook logs: `paymongo_webhook_log.txt`
- Monitor via: [Webhook Test Page](webhook_ngrok_test.php)
- Staff rental view shows real-time updates
