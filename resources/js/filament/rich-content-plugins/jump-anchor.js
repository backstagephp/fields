import { Mark } from '@tiptap/core'

export default Mark.create({
    name: 'jumpAnchor',

    addOptions() {
        return {
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
                        'id': attributes['data-anchor-id'],
                    }
                },
            },
            'anchorId': {
                default: null,
                parseHTML: element => element.getAttribute('data-anchor-id'),
                renderHTML: attributes => {
                    if (!attributes['anchorId']) {
                        return {}
                    }
                    return {
                        'data-anchor-id': attributes['anchorId'],
                        'id': attributes['anchorId'],
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
            {
                tag: 'span[id]',
                getAttrs: element => {
                    const id = element.getAttribute('id')
                    return id ? { 'data-anchor-id': id } : false
                },
            },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', HTMLAttributes, 0]
    },
})