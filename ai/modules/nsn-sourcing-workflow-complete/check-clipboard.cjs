const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const html = fs.readFileSync('resources/views/filament/pages/fetch-nsn-data.blade.php', 'utf8');
const handler = html.match(/x-on:click="([^"]*navigator\.clipboard[^\"]*)"/)[1].replace(/@js\(\$reference\['cage_code'\]\)/g, "'24039'");
(async () => {
  for (const mode of ['missing', 'denied', 'success']) {
    let actual;
    const state = { copied: false, failed: false, Promise, navigator: mode === 'missing' ? {} : { clipboard: { writeText: async value => { actual = value; if (mode === 'denied') throw new Error('denied'); } } } };
    vm.runInNewContext(handler, state);
    await new Promise(setImmediate);
    assert.equal(state.copied, mode === 'success');
    assert.equal(state.failed, mode !== 'success');
    if (mode !== 'missing') assert.equal(actual, '24039');
  }
  console.log('PASS: CAGE copy succeeds, denied access falls back, missing clipboard falls back.');
})().catch(error => { console.error(error.message); process.exitCode = 1; });
