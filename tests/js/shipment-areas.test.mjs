import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import assert from 'node:assert/strict';
import test from 'node:test';

test('shipment dropdown renders grouped areas using the global weight formatter', () => {
    const view = readFileSync(new URL('../../resources/views/pages/sales/delivery-note/form.blade.php', import.meta.url), 'utf8');
    const method = view.match(/^availableAreas\(row\).*$/m)[0];
    const app = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');
    const formatter = app.match(/window\.formatWeight = function[\s\S]*?^};/m)[0];
    const component = runInNewContext(`${formatter}\n({${method}})`, { window: {} });
    component.sourceItem = () => ({ quality_grade_id: 7 });
    component.batches = [
        { warehouse_area_id: 1, area_name: 'Area A', quality_grade_id: 7, weight_kg: '100.50', packages: 4 },
        { warehouse_area_id: 1, area_name: 'Area A', quality_grade_id: 7, weight_kg: '50.25', packages: 2 },
        { warehouse_area_id: 2, area_name: 'Area B', quality_grade_id: 8, weight_kg: 200, packages: 8 },
    ];
    const areas = component.availableAreas({});
    assert.equal(areas.length, 1);
    assert.equal(areas[0].id, 1);
    assert.equal(areas[0].label, 'Area A / 150,75 Kg / 6 kemasan');
    component.batches = [];
    assert.equal(component.availableAreas({}).length, 0);
});
