const { THEMENAME } = require("../env.config");
const fs = require("fs-extra");
const readline = require("readline");
const fileHound = require("filehound");

module.exports = {
  addMainCss,
  getEnv,
  getFilesByExtension,
  getScreenshot
};

async function addMainCss() {
  const ENV = getEnv();

  const rl = readline.createInterface({
    input: await fs.createReadStream("./style.css")
  });

  let modifiedData = "";

  // This switch a theme name to test and deploy built theme easily.
  rl.on("line", line => {
    let regExp = /\Theme Name:/;
    if (regExp.exec(line) !== null && ENV == "development") {
      modifiedData += `Theme Name: ${THEMENAME}-DEV\n`;
    } else if (regExp.exec(line) !== null && ENV == "production") {
      modifiedData += `Theme Name: ${THEMENAME}\n`;
    } else {
      modifiedData += `${line}\n`;
    }
  });

  rl.on("close", async () => {
    if (ENV == "production") await fs.copy("./style.css", "./style.tmp");
    if (ENV == "development")
      await fs.copy("./style.css", "./compiled/main.css");
    await fs.writeFile("./style.css", modifiedData, "utf8");
  });
}

function getEnv() {
  const target = process.env.npm_lifecycle_event;

  switch (target) {
    case "start":
      return "development";

    case "build":
      return "production";

    default:
      return "development";
  }
}

function getFilesByExtension(path, ext) {
  return fileHound
    .create()
    .paths(path)
    .discard("node_modules")
    .discard("build")
    .ext(ext)
    .depth(99)
    .find();
}

function getScreenshot(path) {
  return fileHound
    .create()
    .paths(path)
    .depth(0)
    .glob("screenshot.png")
    .find();
}
