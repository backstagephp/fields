import { Extension } from '@tiptap/core'

export default Extension.create({
    name: 'jumpAnchor',

    addOptions() {
        return {
            types: ['textStyle'],
            HTMLAttributes: {},
        }
    },

    addAttributes() {
        return {
            'data-anchor-id': {
                default: null,
                parseHTML: element => element.getAttribute('data-anchor-id'),
                renderHTML: attributes => {
                    if (!attributes['data-anchor-id']) {
                        return {}
                    }
                    return {
                        'data-anchor-id': attributes['data-anchor-id'],
                    }
                },
            },
        }
    },

    addCommands() {
        return {
            setJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.setMark(this.name, attributes)
                },
            unsetJumpAnchor:
                () =>
                ({ commands }) => {
                    return commands.unsetMark(this.name)
                },
            toggleJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.toggleMark(this.name, attributes)
                },
        }
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-anchor-id]',
            },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', HTMLAttributes, 0]
    },
})