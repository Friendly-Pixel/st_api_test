export interface Post {
  id: number;
  title: string;
  content: string;
  createdAt: string;
  secret?: string;
  tags?: Tag[];
}

export interface Tag {
  id: number;
  name: string;
  color: string;
  createdBy?: User;
}

export interface User {
  id: number;
  email: string;
  father?: User;
}

export type PostResponse = Post & {
  tags: (Tag & {
    color?: string;
    createdBy: User;
  })[];
};

// export type PostResponse2 = DeepPartialExcept<Post, 'tags' | 'createdBy'>;

// interface Person {
//   name: string;
//   age: number;
//   address: {
//     city: string;
//     street: string;
//     zip: number;
//     country: {
//       code: string;
//       name: string;
//     };
//   };
// }

// const TestCase_1: DeepPartialExcept<
//   Person,
//   'address.street' | 'age' | 'address.country.code'
// > = {
//   age: 99,
//   address: {
//     street: 'Quebec',
//     country: {
//       code: undefined,
//     },
//   },
//   name: undefined,
// };
