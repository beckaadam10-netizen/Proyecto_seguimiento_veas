/**
 * Script para que el Sistema de Seguimiento VEAS suba sus respaldos a Google Drive.
 *
 * Se publica como "Aplicación web" desde la cuenta de Google dueña de la carpeta.
 * Cuando el sistema le envía la clave correcta, devuelve un permiso temporal (1 hora)
 * para subir el respaldo a la carpeta indicada abajo. Sin la clave no devuelve nada.
 *
 * El permiso de ESCRITURA en Drive se declara en appsscript.json ("oauthScopes"): sin
 * eso Apps Script ve que este código solo lee la carpeta y da un permiso de solo
 * lectura, y Drive rechaza la subida ("insufficient authentication scopes").
 */

// La misma clave que GOOGLE_DRIVE_CLAVE en el .env del servidor.
const CLAVE = 'PEGAR_ACA_LA_CLAVE';

// ID de la carpeta de Drive (lo que sigue a /folders/ en el link de la carpeta).
const CARPETA = '1e1op6MopzemiUeUs0gvL0Dn4AedDaiPu';

function doPost(e) {
  let datos = {};
  try {
    datos = JSON.parse(e.postData.contents);
  } catch (error) {
    return responder({ error: 'Pedido inválido' });
  }

  if (!datos.clave || datos.clave !== CLAVE) {
    return responder({ error: 'Clave incorrecta' });
  }

  // Comprueba que la carpeta exista y que esta cuenta tenga acceso.
  const carpeta = DriveApp.getFolderById(CARPETA);

  return responder({
    token: ScriptApp.getOAuthToken(),
    carpeta: CARPETA,
    nombre: carpeta.getName(),
  });
}

// Ejecutar esta función una vez desde el editor (botón "Ejecutar") para dar el permiso
// de Drive. Hay que volver a hacerlo si se cambian los permisos de appsscript.json.
function autorizar() {
  Logger.log('Acceso correcto a la carpeta: ' + DriveApp.getFolderById(CARPETA).getName());
}

function responder(objeto) {
  return ContentService.createTextOutput(JSON.stringify(objeto)).setMimeType(ContentService.MimeType.JSON);
}
