import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
const root = new URL('../../', import.meta.url);
const tools = JSON.parse(await readFile(new URL('app/Content/AssessmentTools.json', root), 'utf8'));
const source = await readFile(new URL('public/assets/js/pages/assessment-score.js', root), 'utf8');
const { scoreAssessment } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
let checks = 0;
for (const tool of Object.values(tools)) {
  const count = tool.questions.length, maximum = tool.labels.length;
  for (const invalid of [[], Array(count - 1).fill(1), Array(count + 1).fill(1), Array(count), Array(count).fill(null), Array(count).fill('1'), Array(count).fill(1.5), Array(count).fill(0), Array(count).fill(maximum + 1)]) {
    assert.throws(() => scoreAssessment(tool, invalid)); checks++;
  }
  const highestPositive = tool.questions.map((_, i) => tool.reverse.includes(i + 1) ? 1 : maximum);
  const lowestPositive = highestPositive.map(value => maximum + 1 - value);
  const high = scoreAssessment(tool, highestPositive), low = scoreAssessment(tool, lowestPositive);
  for (const dimension of high.dimensions) { assert.equal(dimension.mean, maximum); assert.equal(dimension.sum, dimension.max); checks += 2; }
  for (const dimension of low.dimensions) { assert.equal(dimension.mean, 1); assert.equal(dimension.sum, dimension.min); checks += 2; }
  if (tool.overall) { assert.equal(high.overall.mean, maximum); assert.equal(low.overall.mean, 1); checks += 2; }
  else { assert.equal(high.overall, null); checks++; }
}
const ryff = scoreAssessment(tools['ryff-wellbeing'], Array(18).fill(1));
assert.equal(ryff.overall.sum, 58); checks++;
assert.equal(ryff.dimensions.find(item => item.name === 'روابط مثبت').sum, 13); checks++;
const burnout = scoreAssessment(tools['maslach-burnout'], Array(22).fill(5));
assert.deepEqual(burnout.dimensions.map(item => item.sum), [45, 25, 40]); checks++;
assert.equal(burnout.dimensions[2].direction, 'positive'); checks++;
console.log(`PASS: ${checks} scoring boundary and reverse-key checks.`);
