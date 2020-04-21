<script>
  (function ($) {
    $(() => {
      $('#backup_string').keyup(onChangeBackup);
      $('#userID,#password,#host,#port').keyup(onChangeInputs);
      $('#ssl').change(onChangeInputs);
      serializeBackup();
    });

    function onChangeBackup(e) {
      let str = e.target.value;
      try {
        str = str === '' ? 'lndhub://:' : str;
        const backup = decodeLndURL(str);
        deserializeBackup(backup);
      } catch (e) {
        $('#backup_string').focus();
      }
    }

    function onChangeInputs() {
      serializeBackup();
    }

    function deserializeBackup(backup) {
      $("#userID").val(backup.username);
      $("#password").val(backup.password);
      $("#host").val(backup.server.host);
      $("#port").val(backup.server.port);
      $("#ssl").prop('checked', backup.server.ssl);
    }

    function serializeBackup() {
      const def = {
        host: 'lndhub.herokuapp.com',
        port: '',
        ssl: 'https'
      };
      let userID = $("#userID").val();
      let password = $("#password").val();
      let host = $("#host").val() !== '' ? $("#host").val() : def.host;
      let ssl = $("#ssl").prop('checked') ? 'https' : 'http';
      let port = $("#port").val() != (ssl=='https'?443:80) ? ':' + $("#port").val() : '';

      let server = (host === def.host && port === def.port && ssl === def.ssl) ? '' : '@' + ssl + '://' + host + port

      const backup = 'lndhub://' + userID + ':' + password + server;
      $('#backup_string').val(backup);
    }
  }
)(jQuery)
</script>

<?
