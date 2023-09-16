export default function plural(word, count) {
    if (count < 2) {
        return word;
    }

    word = word.toString();

    const vowels = ['a', 'e', 'i', 'o', 'u', 'y'];
    let suffix = 's';

    let lastLetter = word.substr(-1, 1);
    const isUpperCase = (lastLetter == lastLetter.toUpperCase());

    lastLetter = lastLetter.toLowerCase();

    if (lastLetter == 'y') {
        const beforeY = word.substr(-2, 1);
        if (vowels.indexOf(beforeY) > -1) {
            suffix = 's';
        } else {
            word = word.substr(0, word.length - 1);
            suffix = 'ies';
        }
    } else if (['x', 's'].indexOf(lastLetter) > -1) {
        suffix = 'es';
    }

    return word + (isUpperCase ? suffix.toUpperCase() : suffix);
}