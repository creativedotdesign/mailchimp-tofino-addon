# Tofino MailChimp Addon

## Work In Progress

A WordPress plugin to add MailChimp API v3 subscribe to list functionality to the Tofino Theme.

* Uses AjaxForm jQuery and PHP functions which is core to the Tofino Theme.
* MailChimp API Errors are logged to the web servers Error Log.
* Settings Controlled via WP Customizer.

### Example Integration

#### Javascript

```
$('.tofino-mc-form').ajaxForm();
```

#### HTML Form

```
<div class="js-form-result"></div>

<form class="form-inline tofino-mc-form" data-wp-action="tofino-mc-form">
  <div class="form-group">
    <label class="sr-only" for="email">Email</label>
    <input type="email" name="email" class="form-control form-email-input" id="email" placeholder="Your email address...">
  </div>
  <button type="submit" class="btn btn-primary"><?php _e('Subscribe'); ?></button>
</form>
```
