import type { Post, PostResponse } from './interfaces';

let a: Post;
a!.tags[0].name = 'hio';

let b: PostResponse;
b!.tags[0].name = 'hio';
b!.tags[0].createdBy.email = 'hio';
