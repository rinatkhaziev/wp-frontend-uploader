<?php global $fu_result; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>User submitted content on <?php bloginfo( 'sitename' ) ?></title>

<style type="text/css">

  /* reset */
  #outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
  .ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */
  .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Forces Hotmail to display normal line spacing.  More on that: http://www.emailonacid.com/forum/viewthread/43/ */
  table td {border-collapse: collapse;} /* Outlook 07, 10 padding issue fix */
  table {border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; } /* remove spacing around Outlook 07, 10 tables */

  /* bring inline */
  img {display: block; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;}
  a img {border: none;}
  a.phone {text-decoration: none; color: #000001 !important; pointer-events: auto; cursor: default;} /* phone link, use as wrapper on phone numbers */
  span {font-size: 13px; line-height: 17px; font-family: monospace; color: #000001;}
</style>
<!--[if gte mso 9]>
  <style>
  /* Target Outlook 2007 and 2010 */
  </style>
<![endif]-->
</head>
<body style="width:100%; margin:0; padding:0; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

<!-- body wrapper -->
<table cellpadding="0" cellspacing="0" border="0" style="margin:0; padding:0; width:100%; line-height: 100% !important;">
  <tr>
    <td valign="top">
      <!-- edge wrapper -->
      <table cellpadding="0" cellspacing="0" border="0" align="center" width="600" style="background: #efefef;">
        <tr>
          <td valign="top">
            <!-- content wrapper -->
            <table cellpadding="0" cellspacing="0" border="0" align="center" width="560" style="background: #efefef;">
              <tr>
                <td valign="top" style="vertical-align: top;">
<!-- ///////////////////////////////////////////////////// -->

<table cellpadding="0" cellspacing="0" border="0" align="center">
  <tr>
    <td valign="top" style="vertical-align: top;">
      <p><?php echo wp_kses_post( $this->settings['admin_notification_text'] ); ?></p>
    </td>
  </tr>
</table>

<?php if ( isset( $fu_result['post_id'] ) && $fu_result['post_id'] ):
$obj = get_post( $fu_result['post_id'] );
?>

<table cellpadding="0" cellspacing="0" border="0" align="center">
  <tr>
    <td valign="top" style="vertical-align: top;">
      <h2>Submitted Text</h2>
    </td>
  </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" align="center">
  <tr>
    <td valign="top" style="vertical-align: top;">
      <h3><?php echo esc_html( $obj->post_title ) ?></h3>
      <?php echo wp_kses_post( wpautop( $obj->post_content ) ) ?>
    </td>
  </tr>
</table>


<?php endif ?>

<?php if ( isset( $fu_result['media_ids'] ) && $fu_result['media_ids'] ): ?>

<table cellpadding="0" cellspacing="0" border="0" align="center">
  <tr>
    <td valign="top" style="vertical-align: top;">
      <h2>Submitted Images</h2>
    </td>
  </tr>
</table>

  <?php foreach( $fu_result['media_ids'] as $media_id ):
      $type = get_post_mime_type( $media_id );

      if ( ! $type || ! stristr( $type, 'image' ) )
        continue;

      $obj = get_post( $media_id );
  ?>

  <table cellpadding="0" cellspacing="0" border="0" align="center">
    <tr>
      <td valign="top" style="vertical-align: top;">
       <?php echo wp_get_attachment_image( $media_id, 'large' ); ?>
       <p><?php echo esc_html( $obj->post_title ); ?></p>
       <?php echo wp_kses_post( wpautop( $obj->post_content ) ); ?>
      </td>
    </tr>
  </table>

  <?php endforeach; ?>

<?php endif ?>


<table cellpadding="0" cellspacing="0" border="0" align="center">
  <tr height="30">
    <td valign="top" style="vertical-align: top; background: #efefef;" width="600" >
    </td>
  </tr>
</table>

<!-- //////////// -->
                </td>
              </tr>
            </table>
            <!-- / content wrapper -->
          </td>
        </tr>
      </table>
      <!-- / edge wrapper -->
    </td>
  </tr>
</table>
<!-- / page wrapper -->
</body>
</html>
