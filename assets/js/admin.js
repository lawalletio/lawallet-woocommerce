function convertSats(sats, ticker) {
  return ticker.currency + ' ' + formatMoney(sats/100000000 * ticker.rate, 2);
}


function formatMoney(amount, decimalCount = 2, decimal = ".", thousands = ",") {
  try {
    decimalCount = Math.abs(decimalCount);
    decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

    const negativeSign = amount < 0 ? "-" : "";

    let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
    let j = (i.length > 3) ? i.length % 3 : 0;

    return negativeSign + (j ? i.substr(0, j) + thousands : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) + (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");
  } catch (e) {
    console.log(e)
  }
}

function decodeLndURL(str) {
  const regex = /lndhub:\/\/([\d\w]*):([\d\w]*)@?((https?):\/\/(.+):?(\d)?)?/i;
  const res = regex.exec(str);
  if (res === null) {
    throw 'invalid format';
  }
  let server = new URL(res[3] !== undefined ? res[3] : 'https://lndhub.herokuapp.com/');
  return {
    username: res[1],
    password: res[2],
    server: {
      host: server.hostname,
      port: server.port != '' ? server.port : (server.protocol === 'https:' ? '443' : '80'),
      ssl: server.protocol === 'https:'
    }
  };
}
