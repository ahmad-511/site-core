export default class Template {
    constructor(template) {
        template = this.enumerateLoops(template);
        template = this.enumerateConditions(template);

        this.template = template;

    }

    escapeRegExp(str) {
        return str.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g, '\\$&');
    }

    enumerateLoops(str = '') {
        // Enumerate nested loops
        const rCounter = /\[(for)\s+.+?\s+in\s+.+?\s*\]|\[(endfor)\]/gsim;

        let counter = 0;
        str = str.replace(rCounter, (m, g1, g2) => {
            let r = m;

            if (g1) {
                counter++;
                r = m.replace(g1, `${g1}:${counter}`);
            }

            if (g2) {
                r = m.replace(g2, `${g2}:${counter}`);
                counter--;
            }

            return r;
        });

        return str;
    }

    enumerateConditions(str = '') {
        // Enumerate nested conditions
        const rCounter = /\[(if)\s+.*?\s*\]|\[(endif)\]/gsim;

        let counter = 0;
        str = str.replace(rCounter, (m, g1, g2) => {
            let r = m;

            if (g1) {
                counter++;
                r = m.replace(g1, `${g1}:${counter}`);
            }

            if (g2) {
                r = m.replace(g2, `${g2}:${counter}`);
                counter--;
            }

            return r;
        });

        return str;
    }

    render(data, tmp, prefix) {
        tmp = tmp || this.template || '';

        if (data instanceof Array) {
            tmp = this.parseArray(data, tmp, prefix);
        } else if (typeof data == 'object') {
            tmp = this.parseObject(data, tmp, prefix);
        }

        // Parse for-in loops
        if (data instanceof Array) {
            tmp = this.parseLoops(data, tmp, prefix);
        }

        // Parse math expressions
        if (!(data instanceof Array)) {
            tmp = this.parseExpressions(tmp);
        }

        // Parse conditions
        tmp = this.parseConditions(tmp);

        // Decode quotes
        tmp = tmp.replace(/\\x27/g, "'").replace(/\\x22/g, '"');

        return tmp;
    }

    parseArray(data, str, prefix) {
        prefix = prefix || '';

        for (const [key, value] of Object.entries(data)) {
            if (typeof value != 'object') {
                // Encode quotes for expression parsing
                let val = value;
                if (typeof value == 'string') {
                    val = value.replace(/'/g, '\\x27').replace(/"/g, '\\x22');
                }

                // str = str.replaceAll(`{${prefix}[${key}]}`, val);
                str = str.replace(new RegExp(this.escapeRegExp(`{${prefix}[${key}]}`), 'g'), val);
            } else {
                if (prefix) {
                    prefix = `${prefix}.`;
                }

                //TODO: what if value is object
                // str = this.render(value, str, `${prefix}[${key}]`);
            }
        }

        return str;
    }

    parseObject(data, str, prefix) {
        if (!(data && typeof data == 'object')) {
            return str;
        }

        prefix = prefix || '';

        if (prefix) {
            prefix = `${prefix}.`;
        }

        for (const [key, value] of Object.entries(data)) {
            if (typeof value != 'object') {
                // Encode quotes for expression parsing
                let val = value;
                if (typeof value == 'string') {
                    val = value.replace(/'/g, '\\x27').replace(/"/g, '\\x22');
                }

                // str = str.replaceAll(`{${prefix}${key}}`, val);
                str = str.replace(new RegExp(this.escapeRegExp(`{${prefix}${key}}`), 'g'), val);
            } else {
                str = this.render(value, str, `${prefix}${key}`);
            }
        }

        return str;
    }

    parseExpressions(str) {
        const rExp = /\{\{(.*?)\}\}/gsim;
        const matches = str.matchAll(rExp);

        for (const match of matches) {
            const exp = match[1].trim().replace(/\n/g, '');

            let val = exp;
            try {
                val = Function(`
                    "use strict";
                    return (${exp});
                `)();
            } catch (err) {

            }

            str = str.replace(match[0], val);
        }

        return str;
    }

    parseLoops(data, str, prefix = '*') {
        const rFor = /\[for:(\d+)\s+(.+?)\s+in\s+(.+?)\s*\](.*?)\[endfor:\1\]/gsim;
        const matches = str.matchAll(rFor);

        for (const match of matches) {
            const groupItem = match[2].trim();
            const group = match[3].trim();
            const segment = match[4];

            if (group != prefix) {
                continue;
            }

            const dataRepeat = [];

            if (data instanceof Array) {
                for (const obj of data) {
                    let tmpSegment = segment;

                    if (typeof obj == 'object') {
                        tmpSegment = this.render(obj, tmpSegment, `${groupItem}`)
                    } else {
                        tmpSegment = tmpSegment.replace(new RegExp(this.escapeRegExp(`{${groupItem}}`), 'g'), obj);
                    }

                    dataRepeat.push(tmpSegment);
                }
            }

            const result = dataRepeat.join('');

            str = str.replace(match[0], result);
        }

        return str;
    }

    parseConditions(str) {
        const rIf = /\[if:(\d+)\s+(.*?)\s*\](.*?)\[endif:\1\]/gsim;
        const matches = str.matchAll(rIf);

        for (const match of matches) {
            const contidtion = match[2].trim().replace(/\n/g, '');
            const result = this.parse(contidtion);

            if (result instanceof Error) continue;

            if (result) {
                str = str.replace(match[0], match[3]);
            } else {
                str = str.replace(match[0], '');
            }
        }

        return str;
    }

    parse(str) {
        try {
            return Function(`
                "use strict";
                return (${str});
            `)();
        } catch (err) {
            return err;
        }
    }
}