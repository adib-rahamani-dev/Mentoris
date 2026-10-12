/** Pure calculation: no network, storage, diagnosis or population percentile. */
export function scoreAssessment(tool, answers) {
  const maximum = tool.labels.length;
  if (!Array.isArray(answers) || answers.length !== tool.questions.length) throw new Error('پاسخ همهٔ سؤال‌ها لازم است.');
  const scored = [];
  for (let i = 0; i < answers.length; i++) {
    const answer = answers[i];
    if (!Number.isInteger(answer) || answer < 1 || answer > maximum) throw new Error('برای همهٔ سؤال‌ها یک گزینهٔ معتبر انتخاب کنید.');
    scored.push(tool.reverse.includes(i + 1) ? maximum + 1 - answer : answer);
  }
  const dimensions = tool.dimensions.map(dimension => {
    const sum = dimension.items.reduce((total, item) => total + scored[item - 1], 0);
    return { ...dimension, sum, mean: sum / dimension.items.length, min: dimension.items.length, max: dimension.items.length * maximum };
  });
  const sum = scored.reduce((total, value) => total + value, 0);
  return { dimensions, overall: tool.overall ? { sum, mean: sum / scored.length, min: scored.length, max: scored.length * maximum } : null };
}
